# Phase 7 : Operations et Maintenance

Cette phase couvre le monitoring, le CI/CD complet, la securite
et les procedures de backup/restore.

> **Contexte** : On a un VPS Hostinger Ubuntu avec K3s, une application
> Laravel deployée via Helm. Maintenant, il faut pouvoir **surveiller**
> que tout fonctionne, **automatiser** les déploiements, et **se preparer**
> aux pannes.

---

## 7.1 Monitoring avec Prometheus et Grafana

### Pourquoi monitorer ?

Sans monitoring, on découvre les problèmes quand les utilisateurs se plaignent.
Avec monitoring, on voit les problèmes **avant** qu'ils impactent les utilisateurs :
disque qui se remplit, CPU qui sature, erreurs qui augmentent, etc.

### Les composants

| Composant         | Role                                                           | Analogie                  |
|-------------------|----------------------------------------------------------------|---------------------------|
| **Prometheus**    | Collecte et stocke les metriques (CPU, RAM, requetes, erreurs) | Le thermometre            |
| **Grafana**       | Affiche les métriques sous forme de graphiques et dashboards   | L'ecran du thermometre    |
| **Alertmanager**  | Envoie des alertes quand les metriques depassent des seuils    | L'alarme                  |
| **Node Exporter** | Expose les metriques du serveur (CPU, RAM, disque)             | Le capteur sur le serveur |

**kube-prometheus-stack** installe tout ca en une seule commande Helm.

### Installation de la stack

> **Attention aux ressources** : la stack de monitoring consomme de la RAM.
> Sur un VPS 4 GB, ca peut etre serre. Ajuster les valeurs en consequence
> ou desactiver certains composants non essentiels.

```bash
# Ajouter le depot Helm
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update

# Installer kube-prometheus-stack
# Inclut : Prometheus, Grafana, Alertmanager, Node Exporter, kube-state-metrics
helm install monitoring prometheus-community/kube-prometheus-stack \
  --namespace monitoring \
  --create-namespace \
  --set prometheus.prometheusSpec.retention=15d \
  --set prometheus.prometheusSpec.storageSpec.volumeClaimTemplate.spec.resources.requests.storage=20Gi \
  --set grafana.adminPassword=changeme \
  --set grafana.persistence.enabled=true \
  --set grafana.persistence.size=5Gi
```

### Acceder a Grafana

Deux options pour acceder a l'interface Grafana :

```bash
# Option 1 : port-forward (temporaire, pour les tests)
# Accessible a http://localhost:3000 depuis ta machine locale
kubectl port-forward svc/monitoring-grafana 3000:80 -n monitoring

# Option 2 : via un Ingress (permanent, accessible sur un sous-domaine)
# Necessite que le domaine grafana.myapp.com pointe vers l'IP du VPS
cat <<EOF | kubectl apply -f -
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: grafana
  namespace: monitoring
  annotations:
    cert-manager.io/cluster-issuer: letsencrypt-prod
spec:
  ingressClassName: nginx
  rules:
    - host: grafana.myapp.com
      http:
        paths:
          - path: /
            pathType: Prefix
            backend:
              service:
                name: monitoring-grafana
                port:
                  number: 80
  tls:
    - hosts:
        - grafana.myapp.com
      secretName: grafana-tls
EOF
```

### Metriques Laravel personnalisees

Par defaut, Prometheus collecte les metriques systeme (CPU, RAM, etc.)
et Kubernetes (pods, deployments, etc.). Pour monitorer l'application
elle-meme (nombre de requetes, latence, erreurs), il faut exposer
des metriques custom depuis Laravel.

On utilise le package `promphp/prometheus_client_php` qui cree un endpoint
`/metrics` au format Prometheus.

```php
// app/Providers/PrometheusServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class PrometheusServiceProvider extends ServiceProvider
{
    public function register()
    {
        // On utilise Redis comme backend pour stocker les metriques
        // (partage entre les replicas PHP)
        $this->app->singleton(CollectorRegistry::class, function () {
            return new CollectorRegistry(
                new Redis(['host' => config('database.redis.default.host')])
            );
        });
    }

    public function boot()
    {
        $registry = $this->app->make(CollectorRegistry::class);

        // Compteur : nombre total de requetes HTTP
        // Labels : methode (GET/POST), route, code de statut
        $counter = $registry->getOrRegisterCounter(
            'app',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'route', 'status']
        );

        // Histogramme : distribution de la latence des requetes
        // Les "buckets" definissent les intervalles de mesure
        // (10ms, 50ms, 100ms, 500ms, 1s, 5s)
        $histogram = $registry->getOrRegisterHistogram(
            'app',
            'http_request_duration_seconds',
            'HTTP request duration',
            ['method', 'route'],
            [0.01, 0.05, 0.1, 0.5, 1, 5]
        );
    }
}
```

```php
// routes/web.php

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

// Endpoint /metrics que Prometheus scrape periodiquement
// Protege par auth.basic pour eviter que n'importe qui y accede
Route::get('/metrics', function (CollectorRegistry $registry) {
    $renderer = new RenderTextFormat();
    return response($renderer->render($registry->getMetricFamilySamples()))
        ->header('Content-Type', RenderTextFormat::MIME_TYPE);
})->middleware('auth.basic');
```

### ServiceMonitor pour Prometheus

Un ServiceMonitor est un objet Kubernetes qui dit a Prometheus
"scrape les metriques de ce service toutes les 30 secondes sur /metrics".
Sans ca, Prometheus ne sait pas que l'app expose des metriques.

```yaml
# infra/k8s/helm/myapp/templates/servicemonitor.yaml

{{- if .Values.metrics.enabled }}
apiVersion: monitoring.coreos.com/v1
kind: ServiceMonitor
metadata:
  name: {{ include "myapp.fullname" . }}
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
spec:
  selector:
    matchLabels:
      {{- include "myapp.selectorLabels" . | nindent 6 }}
      app.kubernetes.io/component: nginx
  endpoints:
    - port: http
      path: /metrics
      interval: 30s
      basicAuth:
        username:
          name: {{ include "myapp.fullname" . }}-metrics-auth
          key: username
        password:
          name: {{ include "myapp.fullname" . }}-metrics-auth
          key: password
{{- end }}
```

### Dashboards Grafana

Grafana a une bibliotheque de dashboards preconfigures. Il suffit d'importer
leur ID pour avoir des graphiques prets a l'emploi :

| ID | Dashboard | Montre quoi |
|----|-----------|-------------|
| **1860** | Node Exporter Full | CPU, RAM, disque, reseau du VPS |
| **13770** | K8s Views / Pods | Ressources par pod, restarts, status |
| **14981** | NGINX Ingress Controller | Requetes/s, latence, erreurs HTTP |
| **763** | Redis Dashboard | Commandes/s, memoire, connexions |
| **9628** | PostgreSQL Database | Requetes, connexions, taille des tables |

Pour importer : Grafana > `+` > Import > entrer l'ID > Load.

---

## 7.2 Logging avec Loki

### Pourquoi Loki ?

Les logs Kubernetes (`kubectl logs`) sont ephemeres : quand un pod redamarre,
ses logs sont perdus. Loki collecte et stocke les logs de tous les pods,
et permet de les rechercher dans Grafana.

**Loki vs ELK (Elasticsearch)** : Loki est beaucoup plus leger. Il n'indexe
pas le contenu des logs (seulement les labels), ce qui le rend ideal pour
un VPS a ressources limitees.

### Installation

```bash
helm repo add grafana https://grafana.github.io/helm-charts
helm repo update

# loki-stack installe Loki + Promtail (l'agent qui collecte les logs)
helm install loki grafana/loki-stack \
  --namespace monitoring \
  --set promtail.enabled=true \
  --set loki.persistence.enabled=true \
  --set loki.persistence.size=10Gi
```

### Configurer Grafana pour Loki

1. Aller dans Grafana > Configuration > Data Sources
2. Add data source > Loki
3. URL : `http://loki:3100`
4. Save & Test

### Requetes LogQL utiles

LogQL est le langage de requete de Loki (comme SQL pour les bases de donnees,
mais pour les logs).

```logql
# Voir les logs d'erreur Laravel
{namespace="production", container="php"} |= "ERROR"

# Logs Nginx avec des codes 5xx (erreurs serveur)
{namespace="production", container="nginx"} | json | status >= 500

# Logs Laravel avec des exceptions (stack traces)
{namespace="production", container="php"} |~ "Exception|Error" | line_format "{{.message}}"

# Compter le nombre d'erreurs par minute (utile pour les alertes)
count_over_time({namespace="production"} |= "ERROR" [1m])
```

---

## 7.3 Alerting

### Pourquoi des alertes ?

Les dashboards c'est bien, mais personne ne regarde Grafana 24h/24.
Les alertes envoient une notification (Slack, email, PagerDuty) quand
quelque chose ne va pas.

**La regle d'or** : n'alerter que sur ce qui necessite une action humaine.
Trop d'alertes = on les ignore toutes. Pas assez = on rate les vrais problemes.

### Regles d'alerte Prometheus

Les regles sont ecrites en PromQL (le langage de requete de Prometheus).
Chaque regle definit : une condition, une duree (combien de temps la condition
doit etre vraie avant d'alerter), une severite, et un message.

```yaml
# infra/k8s/manifests/prometheus-rules.yaml

apiVersion: monitoring.coreos.com/v1
kind: PrometheusRule
metadata:
  name: myapp-alerts
  namespace: monitoring
  labels:
    release: monitoring  # Necessaire pour que Prometheus detecte ces regles
spec:
  groups:
    - name: myapp
      rules:
        # --- ALERTE : Pod en crash loop ---
        # Un pod qui redamarre en boucle indique un bug ou un probleme de config.
        - alert: PodCrashLooping
          expr: rate(kube_pod_container_status_restarts_total{namespace="production"}[15m]) > 0
          for: 5m  # Doit etre vrai pendant 5 min avant d'alerter
          labels:
            severity: critical
          annotations:
            summary: "Pod {{ $labels.pod }} is crash looping"
            description: "Pod {{ $labels.pod }} has restarted {{ $value }} times in the last 15 minutes"

        # --- ALERTE : Taux d'erreur eleve ---
        # Si plus de 5% des requetes retournent une erreur 5xx,
        # quelque chose ne va pas dans l'application.
        - alert: HighErrorRate
          expr: |
            sum(rate(nginx_ingress_controller_requests{status=~"5.."}[5m]))
            /
            sum(rate(nginx_ingress_controller_requests[5m])) > 0.05
          for: 5m
          labels:
            severity: critical
          annotations:
            summary: "High error rate detected"
            description: "Error rate is {{ $value | humanizePercentage }}"

        # --- ALERTE : Latence elevee ---
        # Si le P95 (95e percentile) de la latence depasse 2 secondes,
        # l'application est lente.
        - alert: HighLatency
          expr: |
            histogram_quantile(0.95, sum(rate(nginx_ingress_controller_request_duration_seconds_bucket[5m])) by (le)) > 2
          for: 5m
          labels:
            severity: warning
          annotations:
            summary: "High latency detected"
            description: "P95 latency is {{ $value }}s"

        # --- ALERTE : Espace disque faible ---
        # Moins de 10% d'espace libre = il est temps de nettoyer ou d'agrandir.
        - alert: LowDiskSpace
          expr: |
            (node_filesystem_avail_bytes{mountpoint="/"} / node_filesystem_size_bytes{mountpoint="/"}) < 0.1
          for: 5m
          labels:
            severity: warning
          annotations:
            summary: "Low disk space on {{ $labels.instance }}"
            description: "Only {{ $value | humanizePercentage }} disk space remaining"

        # --- ALERTE : Certificat qui expire bientot ---
        # Cert-manager renouvelle normalement les certificats automatiquement.
        # Si un certificat expire dans moins de 14 jours, quelque chose a echoue.
        - alert: CertificateExpiringSoon
          expr: |
            (certmanager_certificate_expiration_timestamp_seconds - time()) / 86400 < 14
          for: 1h
          labels:
            severity: warning
          annotations:
            summary: "Certificate {{ $labels.name }} expiring soon"
            description: "Certificate expires in {{ $value }} days"
```

### Configuration Alertmanager

Alertmanager recoit les alertes de Prometheus et les envoie vers les bons
canaux (Slack, PagerDuty, email). On peut router les alertes par severite :
les critiques vont sur PagerDuty (reveille quelqu'un la nuit),
les warnings sur Slack (a traiter pendant les heures de bureau).

```yaml
# Dans le values de kube-prometheus-stack
alertmanager:
  config:
    global:
      resolve_timeout: 5m
    route:
      # Regrouper les alertes par nom et namespace
      # (evite de recevoir 10 fois la meme alerte)
      group_by: ['alertname', 'namespace']
      group_wait: 30s       # Attend 30s pour grouper les alertes similaires
      group_interval: 5m    # Envoie un rappel toutes les 5 min si l'alerte persiste
      repeat_interval: 4h   # Re-notifie toutes les 4h tant que c'est pas resolu
      receiver: 'slack'     # Canal par defaut
      routes:
        # Les alertes critiques vont aussi sur PagerDuty
        - match:
            severity: critical
          receiver: 'pagerduty'
        - match:
            severity: warning
          receiver: 'slack'

    receivers:
      - name: 'slack'
        slack_configs:
          - api_url: 'https://hooks.slack.com/services/XXX/YYY/ZZZ'
            channel: '#alerts'
            send_resolved: true  # Notifie aussi quand l'alerte est resolue
            title: '{{ .Status | toUpper }}: {{ .CommonAnnotations.summary }}'
            text: '{{ .CommonAnnotations.description }}'

      - name: 'pagerduty'
        pagerduty_configs:
          - service_key: 'your-pagerduty-key'
            send_resolved: true
```

---

## 7.4 CI/CD complet

### Qu'est-ce que le CI/CD ?

| Terme | Signification | Ce que ca fait |
|-------|---------------|----------------|
| **CI** (Continuous Integration) | Integration continue | A chaque push, on lance les tests et le linting automatiquement |
| **CD** (Continuous Delivery) | Livraison continue | Si les tests passent, on build l'image Docker et on la push |
| **CD** (Continuous Deployment) | Deploiement continu | On deploie automatiquement en staging, et en prod sur tag |

Le pipeline complet : **push** → test → lint → build image → scan securite → deploy staging → deploy prod.

### GitHub Actions - Pipeline complet

Ce fichier definit tout le pipeline CI/CD. Il est declenche sur :
- **Push sur main/develop** : test + build + deploy
- **Tag v*** : test + build + deploy en production
- **Pull request** : test seulement (pas de build ni deploy)

```yaml
# .github/workflows/ci-cd.yml

name: CI/CD Pipeline

on:
  push:
    branches: [main, develop]
    tags: ['v*']
  pull_request:
    branches: [main]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  # ==========================================================================
  # ETAPE 1 : Tests et qualite de code
  # Lance les tests PHPUnit, le linting et l'analyse statique
  # ==========================================================================
  test:
    name: Test
    runs-on: ubuntu-latest
    # "services" cree des conteneurs accessibles pendant les tests
    # (comme un docker-compose temporaire pour le CI)
    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: testing
          POSTGRES_USER: testing
          POSTGRES_PASSWORD: testing
        ports:
          - 5432:5432
        options: --health-cmd pg_isready --health-interval 10s --health-timeout 5s --health-retries 5
      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_pgsql, redis
          coverage: xdebug

      # Cache Composer pour accelerer les builds suivants
      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: app/vendor
          key: composer-${{ hashFiles('app/composer.lock') }}

      - name: Install dependencies
        working-directory: app
        run: composer install --prefer-dist --no-progress

      # Linting + analyse statique : detecte les problemes de style
      # et les bugs potentiels sans executer le code
      - name: Lint
        working-directory: app
        run: |
          vendor/bin/php-cs-fixer fix --dry-run --diff
          vendor/bin/phpstan analyse

      - name: Test
        working-directory: app
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_DATABASE: testing
          DB_USERNAME: testing
          DB_PASSWORD: testing
        run: |
          cp .env.example .env
          php artisan key:generate
          vendor/bin/phpunit --coverage-clover coverage.xml

      # Upload du rapport de couverture vers Codecov
      - name: Upload coverage
        uses: codecov/codecov-action@v4
        with:
          files: app/coverage.xml

  # ==========================================================================
  # ETAPE 2 : Build et push des images Docker
  # Ne s'execute PAS sur les pull requests (juste les tests suffisent)
  # ==========================================================================
  build:
    name: Build
    runs-on: ubuntu-latest
    needs: test  # Attend que les tests passent
    if: github.event_name != 'pull_request'
    permissions:
      contents: read
      packages: write  # Necessaire pour push vers GHCR

    outputs:
      version: ${{ steps.meta.outputs.version }}  # Rend la version accessible aux jobs suivants

    steps:
      - uses: actions/checkout@v4

      # Buildx permet le cache multi-layer et le build multi-architecture
      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Login to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      # metadata-action genere automatiquement les tags Docker
      # en fonction de la branche, du tag git, du SHA
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha,prefix=

      - name: Build and push PHP image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: infra/php/Dockerfile
          target: production-php
          push: true
          tags: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}-php:${{ steps.meta.outputs.version }}
          # Le cache GitHub Actions accelere enormement les builds suivants
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - name: Build and push Nginx image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: infra/nginx/Dockerfile
          push: true
          tags: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}-nginx:${{ steps.meta.outputs.version }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

  # ==========================================================================
  # ETAPE 3 : Scan de securite des images Docker
  # Trivy detecte les vulnerabilites connues dans les images
  # ==========================================================================
  security:
    name: Security Scan
    runs-on: ubuntu-latest
    needs: build
    if: github.event_name != 'pull_request'

    steps:
      - uses: actions/checkout@v4

      - name: Run Trivy vulnerability scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: '${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}-php:${{ needs.build.outputs.version }}'
          format: 'sarif'
          output: 'trivy-results.sarif'

      # Les resultats apparaissent dans l'onglet "Security" du repo GitHub
      - name: Upload Trivy scan results
        uses: github/codeql-action/upload-sarif@v3
        with:
          sarif_file: 'trivy-results.sarif'

  # ==========================================================================
  # ETAPE 4 : Deploiement staging (branche develop)
  # ==========================================================================
  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    needs: [build, security]
    if: github.ref == 'refs/heads/develop'
    # "environment" active les protections GitHub (approval, secrets specifiques)
    environment:
      name: staging
      url: https://staging.myapp.com

    steps:
      - uses: actions/checkout@v4

      - name: Install Helm
        uses: azure/setup-helm@v3

      # Le kubeconfig est stocke en base64 dans les secrets GitHub
      - name: Setup kubeconfig
        run: |
          mkdir -p ~/.kube
          echo "${{ secrets.KUBECONFIG_STAGING }}" | base64 -d > ~/.kube/config
          chmod 600 ~/.kube/config

      - name: Deploy to staging
        run: |
          helm upgrade --install myapp infra/k8s/helm/myapp \
            --namespace staging \
            --create-namespace \
            -f infra/k8s/helm/myapp/values.yaml \
            -f infra/k8s/helm/myapp/values.staging.yaml \
            --set image.php.tag=${{ needs.build.outputs.version }} \
            --set image.nginx.tag=${{ needs.build.outputs.version }} \
            --set app.key=${{ secrets.APP_KEY_STAGING }} \
            --set database.password=${{ secrets.DB_PASSWORD_STAGING }} \
            --set redis.password=${{ secrets.REDIS_PASSWORD_STAGING }} \
            --wait --timeout 10m

      - name: Verify deployment
        run: |
          kubectl rollout status deployment/myapp-php -n staging
          curl -sf https://staging.myapp.com/health

  # ==========================================================================
  # ETAPE 5 : Deploiement production (uniquement sur tag v*)
  # ==========================================================================
  deploy-production:
    name: Deploy to Production
    runs-on: ubuntu-latest
    needs: [build, security]
    # startsWith(github.ref, 'refs/tags/v') = deploie seulement sur un tag git
    # Workflow : on cree un tag "v1.2.3" → ca declenche le deploy en prod
    if: startsWith(github.ref, 'refs/tags/v')
    environment:
      name: production
      url: https://myapp.com

    steps:
      - uses: actions/checkout@v4

      - name: Install Helm
        uses: azure/setup-helm@v3

      - name: Setup kubeconfig
        run: |
          mkdir -p ~/.kube
          echo "${{ secrets.KUBECONFIG_PRODUCTION }}" | base64 -d > ~/.kube/config
          chmod 600 ~/.kube/config

      - name: Deploy to production
        run: |
          helm upgrade --install myapp infra/k8s/helm/myapp \
            --namespace production \
            --create-namespace \
            -f infra/k8s/helm/myapp/values.yaml \
            -f infra/k8s/helm/myapp/values.production.yaml \
            --set image.php.tag=${{ needs.build.outputs.version }} \
            --set image.nginx.tag=${{ needs.build.outputs.version }} \
            --set app.key=${{ secrets.APP_KEY_PRODUCTION }} \
            --set database.password=${{ secrets.DB_PASSWORD_PRODUCTION }} \
            --set redis.password=${{ secrets.REDIS_PASSWORD_PRODUCTION }} \
            --wait --timeout 10m

      - name: Verify deployment
        run: |
          kubectl rollout status deployment/myapp-php -n production
          curl -sf https://myapp.com/health

      # Notification Slack du resultat (succes ou echec)
      - name: Notify Slack
        if: always()
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: "Production deployment ${{ job.status }}: ${{ github.ref_name }}"
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
```

---

## 7.5 GitOps avec Flux CD

### Qu'est-ce que le GitOps ?

Le GitOps est une evolution du CI/CD ou **Git est la source de verite**
pour l'etat du cluster. Au lieu de deployer avec `helm upgrade` depuis
le CI, on met a jour un fichier dans Git et un operateur dans le cluster
(Flux ou ArgoCD) detecte le changement et deploie automatiquement.

**Avantages** :
- L'etat du cluster est toujours synchronise avec Git
- Rollback = revert un commit Git
- Audit trail complet (qui a change quoi, quand)
- Pas besoin de donner le kubeconfig au CI

**Inconvenient** : plus complexe a mettre en place.

> **Recommandation** : commencer par le CI/CD classique (section 7.4),
> puis migrer vers GitOps quand on est a l'aise avec Kubernetes.

### Installation de Flux

```bash
# Installer le CLI Flux
# Linux
curl -s https://fluxcd.io/install.sh | sudo bash

# macOS
brew install fluxcd/tap/flux

# Bootstrap : installe Flux dans le cluster et lie au repo Git
flux bootstrap github \
  --owner=username \
  --repository=myapp \
  --path=infra/flux \
  --personal
```

### Structure Flux

```
infra/flux/
├── clusters/
│   └── production/
│       ├── flux-system/         # Genere par le bootstrap (ne pas toucher)
│       └── apps.yaml            # Reference les applications a deployer
├── apps/
│   └── myapp/
│       ├── kustomization.yaml   # Orchestre le deploiement
│       ├── namespace.yaml       # Cree le namespace
│       ├── helmrelease.yaml     # Deploie le chart Helm
│       └── sealed-secrets/      # Secrets chiffres
└── infrastructure/
    └── sources/
        └── helm-repositories.yaml  # Repos Helm a utiliser
```

### HelmRelease

Le HelmRelease est l'objet central de Flux. Il dit "deploie ce chart Helm
avec ces values". Flux surveille le repo Git et reapplique automatiquement
quand quelque chose change.

```yaml
# infra/flux/apps/myapp/helmrelease.yaml

apiVersion: helm.toolkit.fluxcd.io/v2beta1
kind: HelmRelease
metadata:
  name: myapp
  namespace: production
spec:
  interval: 5m  # Verifie les changements toutes les 5 minutes
  chart:
    spec:
      chart: ./infra/k8s/helm/myapp
      sourceRef:
        kind: GitRepository
        name: flux-system
        namespace: flux-system
  values:
    image:
      php:
        tag: v1.0.0  # Mis a jour automatiquement par l'image automation
      nginx:
        tag: v1.0.0
  valuesFrom:
    # Les secrets viennent d'un Secret Kubernetes (pas du repo Git)
    - kind: Secret
      name: myapp-helm-values
      valuesKey: values.yaml
```

### Image automation

Flux peut aussi surveiller un registre Docker et mettre a jour
automatiquement le tag d'image quand une nouvelle version est poussee.
Ca cree un commit dans le repo Git avec le nouveau tag.

```yaml
# Surveille les nouvelles images sur le registre
apiVersion: image.toolkit.fluxcd.io/v1beta1
kind: ImageRepository
metadata:
  name: myapp-php
  namespace: flux-system
spec:
  image: ghcr.io/username/myapp-php
  interval: 1m  # Verifie toutes les minutes

---
# Politique : prend la derniere version semver >= 1.0.0
apiVersion: image.toolkit.fluxcd.io/v1beta1
kind: ImagePolicy
metadata:
  name: myapp-php
  namespace: flux-system
spec:
  imageRepositoryRef:
    name: myapp-php
  policy:
    semver:
      range: '>=1.0.0'

---
# Quand une nouvelle image est detectee, met a jour le tag
# dans le fichier helmrelease.yaml et commite dans Git
apiVersion: image.toolkit.fluxcd.io/v1beta1
kind: ImageUpdateAutomation
metadata:
  name: myapp
  namespace: flux-system
spec:
  interval: 1m
  sourceRef:
    kind: GitRepository
    name: flux-system
  git:
    checkout:
      ref:
        branch: main
    commit:
      author:
        email: flux@myapp.com
        name: Flux
      messageTemplate: 'chore: update image to {{.NewTag}}'
    push:
      branch: main
  update:
    path: ./infra/flux/apps/myapp
    strategy: Setters
```

---

## 7.6 Securite

### Scan d'images avec Trivy

Trivy scanne les images Docker pour trouver les vulnerabilites connues
(CVE). C'est comme un antivirus pour les images Docker.

**Quand l'utiliser** :
- Dans le CI (voir section 7.4)
- En local avant de push une image
- Regulierement sur les images en production (les CVE sont decouvertes en continu)

```bash
# Installer Trivy
# Linux
curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh

# macOS
brew install trivy

# Scanner une image
trivy image ghcr.io/username/myapp-php:latest

# Scanner uniquement les vulnerabilites critiques
trivy image --severity HIGH,CRITICAL ghcr.io/username/myapp-php:latest

# Generer un rapport JSON
trivy image --format json --output report.json ghcr.io/username/myapp-php:latest
```

### Pod Security Standards

Kubernetes permet de restreindre ce que les pods peuvent faire.
Le niveau `restricted` est le plus strict :
- Le pod doit tourner en tant qu'utilisateur non-root
- Il ne peut pas escalader ses privileges
- Il ne peut pas acceder au filesystem en ecriture (sauf les volumes montes)

```yaml
# Appliquer les standards de securite au namespace
apiVersion: v1
kind: Namespace
metadata:
  name: production
  labels:
    # enforce = bloque les pods non-conformes
    pod-security.kubernetes.io/enforce: restricted
    # audit = log les violations (sans bloquer)
    pod-security.kubernetes.io/audit: restricted
    # warn = affiche un warning a l'utilisateur
    pod-security.kubernetes.io/warn: restricted
```

### SecurityContext dans les pods

Le SecurityContext definit les contraintes de securite pour chaque pod.
C'est ce qui permet de respecter les Pod Security Standards.

```yaml
# Dans le deployment
spec:
  template:
    spec:
      securityContext:
        runAsNonRoot: true       # Interdit de tourner en root
        runAsUser: 1000          # UID de l'utilisateur dans le conteneur
        fsGroup: 1000            # GID pour les fichiers montes
        seccompProfile:
          type: RuntimeDefault   # Profil seccomp par defaut (bloque les syscalls dangereuses)
      containers:
        - name: php
          securityContext:
            allowPrivilegeEscalation: false  # Interdit l'escalade de privileges
            readOnlyRootFilesystem: true     # Filesystem en lecture seule
            capabilities:
              drop:
                - ALL  # Retire toutes les capabilities Linux
          # Comme le filesystem est en lecture seule, on monte
          # des volumes temporaires pour les dossiers qui ont besoin d'ecriture
          volumeMounts:
            - name: tmp
              mountPath: /tmp
            - name: cache
              mountPath: /var/www/html/storage/framework/cache
      volumes:
        - name: tmp
          emptyDir: {}  # Volume temporaire, supprime quand le pod s'arrete
        - name: cache
          emptyDir: {}
```

---

## 7.7 Backup et Disaster Recovery

### Pourquoi des backups ?

Meme avec Kubernetes et la haute disponibilite, les donnees peuvent etre
perdues : erreur humaine (`DELETE FROM users`), corruption, ransomware, etc.
Les backups sont la derniere ligne de defense.

**Regle 3-2-1** :
- 3 copies des donnees
- 2 supports differents (disque local + object storage)
- 1 copie hors-site (dans un autre datacenter)

### Velero pour les backups Kubernetes

Velero sauvegarde l'etat complet du cluster (tous les objets Kubernetes)
et les volumes persistants. Ca permet de restaurer un namespace entier
en une commande.

```bash
# Installer Velero avec un backend S3 compatible
velero install \
  --provider aws \
  --plugins velero/velero-plugin-for-aws:v1.8.0 \
  --bucket velero-backups \
  --secret-file ./credentials-velero \
  --backup-location-config region=eu-west-1,s3ForcePathStyle=true,s3Url=https://s3.eu-west-1.amazonaws.com \
  --use-volume-snapshots=false

# Creer un backup du namespace production
velero backup create myapp-backup --include-namespaces production

# Voir les backups existants
velero backup get

# Restaurer depuis un backup
velero restore create --from-backup myapp-backup
```

### Backup PostgreSQL

Les volumes Kubernetes ne sont pas un bon backup pour les bases de donnees
(risque de corruption si le backup est fait pendant une ecriture).
Il faut utiliser `pg_dump` pour un backup coherent.

Ce CronJob Kubernetes lance un `pg_dump` tous les jours a 2h du matin.

```yaml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: postgres-backup
  namespace: production
spec:
  schedule: "0 2 * * *"  # Tous les jours a 2h du matin
  jobTemplate:
    spec:
      template:
        spec:
          containers:
            - name: backup
              image: postgres:16-alpine
              command:
                - /bin/sh
                - -c
                - |
                  TIMESTAMP=$(date +%Y%m%d_%H%M%S)
                  # pg_dump cree un dump SQL compresse
                  pg_dump -h $DB_HOST -U $DB_USER -d $DB_NAME | gzip > /backup/db_$TIMESTAMP.sql.gz
                  # Upload vers un object storage distant (avec rclone)
                  rclone copy /backup/db_$TIMESTAMP.sql.gz remote:backups/
                  # Nettoyer les backups locaux de plus de 7 jours
                  find /backup -mtime +7 -delete
              envFrom:
                - secretRef:
                    name: myapp-secret
              volumeMounts:
                - name: backup
                  mountPath: /backup
          volumes:
            - name: backup
              persistentVolumeClaim:
                claimName: backup-pvc
          restartPolicy: OnFailure
```

### Runbook (procedures d'urgence)

Un runbook documente les procedures a suivre en cas d'incident.
C'est le guide de survie qu'on consulte a 3h du matin quand tout est casse.

**A creer dans `docs/runbook.md`** :

#### Application down

1. Verifier les pods : `kubectl get pods -n production`
2. Verifier les logs : `kubectl logs -l app=myapp -n production`
3. Verifier les events : `kubectl get events -n production --sort-by='.lastTimestamp'`
4. Rollback si necessaire : `helm rollback myapp -n production`

#### Base de donnees corrompue

1. Identifier le dernier backup valide : `velero backup get`
2. Mettre l'application en maintenance
3. Restaurer le dump : `kubectl exec -it postgres-0 -- psql < backup.sql`
4. Verifier l'integrite des donnees
5. Remettre l'application en ligne

#### Certificat expire

1. Verifier le statut : `kubectl describe certificate -n production`
2. Forcer le renouvellement : `kubectl delete certificate myapp-tls -n production`
3. Cert-manager va recreer automatiquement le certificat

#### Scaling d'urgence

```bash
# Augmenter temporairement le nombre de pods
kubectl scale deployment myapp-php --replicas=10 -n production
```

---

## Checklist de fin de phase

- [ ] Prometheus et Grafana installes et accessibles
- [ ] Dashboards configures (Node Exporter, Pods, Ingress, PostgreSQL, Redis)
- [ ] Loki pour les logs (recherche dans Grafana)
- [ ] Alertes configurees (Slack ou autre canal)
- [ ] CI/CD complet : tests → build → scan → deploy staging → deploy prod
- [ ] Scan de securite Trivy dans le pipeline
- [ ] Deploiement staging automatique sur la branche develop
- [ ] Deploiement production sur tag `v*`
- [ ] (Optionnel) GitOps avec Flux CD
- [ ] Velero pour les backups du cluster
- [ ] Backup PostgreSQL automatique (CronJob)
- [ ] Runbook documente avec les procedures d'urgence
- [ ] Pod Security Standards appliques au namespace production
