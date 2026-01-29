# Phase 6 : Kubernetes en production (VPS)

Cette phase couvre le déploiement de l'application Laravel sur K3s
en production, avec tous les composants necessaires.

> **Contexte** : On déploie sur un **VPS Hostinger Ubuntu** avec K3s
> (installé en Phase 5). K3s est un Kubernetes allege, parfait pour
> un VPS avec des ressources limitées. Tout ce qui suit suppose que K3s est
> opérationnel et que `kubectl get nodes` fonctionne depuis ta machine locale.

---

## 6.1 Architecture cible

Voici comment les composants s'emboitent sur le VPS :

```
                    Internet
                        |
                        v
              +-------------------+
              |   DNS (domain)    |  <-- Le domaine pointe vers l'IP du VPS
              +---------+---------+
                        |
                        v
              +-------------------+
              |  VPS Hostinger    |
              |                   |
              |  +-------------+  |
              |  |    K3s      |  |  <-- Kubernetes allege
              |  |             |  |
              |  | +---------+ |  |
              |  | | Ingress | |  |  <-- nginx-ingress : recoit le trafic HTTP/HTTPS
              |  | +----+----+ |  |      et le route vers le bon service
              |  |      |      |  |
              |  | +----+----+ |  |
              |  | | Laravel | |  |  <-- 2+ replicas PHP-FPM + Nginx
              |  | +----+----+ |  |      (si un pod plante, les autres prennent le relais)
              |  |      |      |  |
              |  | +----+----+ |  |
              |  | |  DB     | |  |  <-- PostgreSQL (donnees persistantes)
              |  | |  Redis  | |  |  <-- Redis (cache, sessions, queues)
              |  | +---------+ |  |
              |  +-------------+  |
              +-------------------+
```

**Pourquoi cette architecture ?**
- **Ingress** : un seul point d'entrée qui gere SSL et le routing
- **Replicas** : plusieurs copies de l'app pour la haute disponibilité
- **Separation** : chaque composant (app, DB, cache) est isole dans son pod

---

## 6.2 Helm Chart pour Laravel

### Qu'est-ce que Helm ?

Helm est le **gestionnaire de paquets** de Kubernetes. Comme `apt` pour Ubuntu
ou `composer` pour PHP, Helm permet d'installer des applications complexes
en une seule commande.

Un **chart** est un paquet Helm : un ensemble de templates YAML qui décrivent
tous les objets Kubernetes necessaires (Deployments, Services, Ingress, etc.).

**Pourquoi Helm plutôt que des fichiers YAML bruts ?**
- **Templates** : on utilise des variables au lieu de dupliquer les fichiers
- **Values** : on peut avoir des configs différentes par environnement
  (dev, staging, production) en changeant juste un fichier de values
- **Releases** : Helm garde un historique des déploiements, ce qui permet
  de rollback en une commande
- **Dependencies** : on peut inclure PostgreSQL et Redis comme sous charts

### Structure du chart

```
infra/k8s/helm/myapp/
├── Chart.yaml                 # Metadata du chart (nom, version, dependances)
├── values.yaml                # Valeurs par defaut
├── values.production.yaml     # Surcharges pour la production
├── values.staging.yaml        # Surcharges pour le staging
├── templates/                 # Les templates YAML Kubernetes
│   ├── _helpers.tpl           # Fonctions reutilisables (noms, labels)
│   ├── namespace.yaml         # Namespace dedie a l'app
│   ├── configmap.yaml         # Variables d'environnement non-sensibles
│   ├── secret.yaml            # Variables sensibles (mots de passe, cles)
│   ├── deployment-php.yaml    # Pods PHP-FPM
│   ├── deployment-nginx.yaml  # Pods Nginx
│   ├── service.yaml           # Services (expose les pods en interne)
│   ├── ingress.yaml           # Ingress (routing HTTP depuis l'exterieur)
│   ├── pvc.yaml               # Volume persistant (storage Laravel)
│   ├── hpa.yaml               # Autoscaling (ajuste le nombre de pods)
│   ├── job-migrate.yaml       # Job de migration (execute apres chaque deploy)
│   └── cronjob-scheduler.yaml # CronJob pour le scheduler Laravel
└── charts/                    # Dependances (PostgreSQL, Redis)
```

### Chart.yaml

Ce fichier décrit le chart et ses dépendances. Les `dependencies` permettent
d'installer automatiquement PostgreSQL et Redis depuis les charts Bitnami
(des charts communautaires bien maintenus).

```yaml
# infra/k8s/helm/myapp/Chart.yaml

apiVersion: v2
name: myapp
description: Laravel application Helm chart
type: application
version: 1.0.0
appVersion: "1.0.0"

dependencies:
  - name: postgresql
    version: "14.x.x"
    repository: "https://charts.bitnami.com/bitnami"
    # "condition" = n'installe PostgreSQL que si postgresql.enabled=true
    condition: postgresql.enabled
  - name: redis
    version: "18.x.x"
    repository: "https://charts.bitnami.com/bitnami"
    condition: redis.enabled
```

### values.yaml (valeurs par defaut)

Ce fichier centralise **toute la configuration** de l'application.
On le surcharge ensuite avec `values.production.yaml` pour la prod.

L'idée : les valeurs par défaut sont raisonnables pour le dev/staging,
et on augmente les ressources pour la production.

```yaml
# infra/k8s/helm/myapp/values.yaml

# Namespace Kubernetes dédié a l'application
namespace: myapp

# Images Docker a deployer
image:
  php:
    repository: ghcr.io/username/myapp-php
    tag: latest
    pullPolicy: IfNotPresent  # Ne re-pull que si l'image n'est pas deja en local
  nginx:
    repository: ghcr.io/username/myapp-nginx
    tag: latest
    pullPolicy: IfNotPresent

imagePullSecrets: []

# Nombre de replicas (copies) de chaque composant
# Plus il y en a, plus l'app est résiliente
replicaCount:
  php: 2
  nginx: 2

# Limites de ressources par pod
# "requests" = minimum garanti, "limits" = maximum autorise
# Si un pod dépasse ses limits, Kubernetes le tue (OOMKilled)
resources:
  php:
    requests:
      memory: "128Mi"
      cpu: "100m"     # 100 millicores = 0.1 CPU
    limits:
      memory: "512Mi"
      cpu: "500m"
  nginx:
    requests:
      memory: "64Mi"
      cpu: "50m"
    limits:
      memory: "128Mi"
      cpu: "200m"

# Autoscaling : ajuste automatiquement le nombre de pods
# en fonction de la charge CPU/RAM
autoscaling:
  enabled: false
  minReplicas: 2
  maxReplicas: 10
  targetCPUUtilizationPercentage: 70
  targetMemoryUtilizationPercentage: 80

# Ingress : expose l'application sur Internet via un domaine
ingress:
  enabled: true
  className: nginx
  annotations:
    # Dit a cert-manager de générer un certificat Let's Encrypt
    cert-manager.io/cluster-issuer: letsencrypt-prod
  hosts:
    - host: myapp.com
      paths:
        - path: /
          pathType: Prefix
  tls:
    - secretName: myapp-tls   # Kubernetes Secret ou le certificat sera stocke
      hosts:
        - myapp.com

# Configuration Laravel
app:
  name: MyApp
  env: production
  debug: false
  url: https://myapp.com
  key: ""  # A definir via --set ou secret (jamais en clair dans le repo)

# Base de donnees
database:
  connection: pgsql
  host: myapp-postgresql   # Nom du service PostgreSQL dans K8s
  port: 5432
  name: app
  username: app
  password: ""  # A definir via --set ou secret

# Redis
redis:
  host: myapp-redis-master  # Nom du service Redis dans K8s
  port: 6379
  password: ""

# Drivers Laravel
cache:
  driver: redis
session:
  driver: redis
queue:
  connection: redis

# Volume persistant pour le storage Laravel (uploads, logs)
storage:
  enabled: true
  size: 5Gi
  storageClassName: ""  # Utilise le StorageClass par defaut de K3s

# Migrations : executees automatiquement apres chaque deploy via un Job
migrations:
  enabled: true
  runOnUpgrade: true

# Scheduler Laravel (cron) : execute "php artisan schedule:run" chaque minute
scheduler:
  enabled: true

# Sous-chart PostgreSQL (Bitnami)
postgresql:
  enabled: true
  auth:
    database: app
    username: app
    password: ""
  primary:
    persistence:
      size: 10Gi

# Sous-chart Redis (Bitnami)
redis:
  enabled: true
  auth:
    password: ""
  master:
    persistence:
      size: 1Gi
```

### values.production.yaml

Ce fichier **surcharge** les valeurs par defaut pour la production.
On augmente les ressources, le nombre de replicas et la taille du stockage.

```yaml
# infra/k8s/helm/myapp/values.production.yaml

namespace: production

image:
  php:
    tag: "v1.0.0"  # En prod, on utilise un tag specifique, jamais "latest"
  nginx:
    tag: "v1.0.0"

# Plus de replicas pour la resilience et les performances
replicaCount:
  php: 3
  nginx: 2

# Plus de ressources en production
resources:
  php:
    requests:
      memory: "256Mi"
      cpu: "200m"
    limits:
      memory: "1Gi"
      cpu: "1000m"

# Autoscaling actif en production
autoscaling:
  enabled: true
  minReplicas: 3
  maxReplicas: 10

ingress:
  hosts:
    - host: myapp.com
      paths:
        - path: /
          pathType: Prefix
  tls:
    - secretName: myapp-tls
      hosts:
        - myapp.com

app:
  env: production
  debug: false
  url: https://myapp.com

# Plus de stockage en production
storage:
  size: 20Gi

postgresql:
  primary:
    persistence:
      size: 50Gi
    resources:
      requests:
        memory: "512Mi"
        cpu: "250m"
```

### Templates principaux

Les templates utilisent le langage de templating Go. Les `{{ }}` sont
remplacé par Helm au moment du déploiement.

Le fichier `_helpers.tpl` définit des fonctions réutilisables dans tous
les autres templates (noms, labels). Ça évite la repetition.

```yaml
# infra/k8s/helm/myapp/templates/_helpers.tpl

{{/*
Nom complet de l'application (utilise le nom de la release Helm)
*/}}
{{- define "myapp.fullname" -}}
{{- printf "%s" .Release.Name | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Labels communs appliques a toutes les ressources.
Ces labels permettent de filtrer et identifier les ressources
dans kubectl (ex: kubectl get all -l app.kubernetes.io/name=myapp)
*/}}
{{- define "myapp.labels" -}}
helm.sh/chart: {{ .Chart.Name }}-{{ .Chart.Version }}
app.kubernetes.io/name: {{ .Chart.Name }}
app.kubernetes.io/instance: {{ .Release.Name }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Labels de selection (utilises par les Deployments pour cibler leurs pods)
*/}}
{{- define "myapp.selectorLabels" -}}
app.kubernetes.io/name: {{ .Chart.Name }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}
```

**ConfigMap** : stocke les variables d'environnement non-sensibles.
Un ConfigMap est un objet Kubernetes qui injecte des variables dans les pods.

```yaml
# infra/k8s/helm/myapp/templates/configmap.yaml

apiVersion: v1
kind: ConfigMap
metadata:
  name: {{ include "myapp.fullname" . }}-config
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
data:
  APP_NAME: {{ .Values.app.name | quote }}
  APP_ENV: {{ .Values.app.env | quote }}
  APP_DEBUG: {{ .Values.app.debug | quote }}
  APP_URL: {{ .Values.app.url | quote }}

  DB_CONNECTION: {{ .Values.database.connection | quote }}
  DB_HOST: {{ .Values.database.host | quote }}
  DB_PORT: {{ .Values.database.port | quote }}
  DB_DATABASE: {{ .Values.database.name | quote }}

  CACHE_DRIVER: {{ .Values.cache.driver | quote }}
  SESSION_DRIVER: {{ .Values.session.driver | quote }}
  QUEUE_CONNECTION: {{ .Values.queue.connection | quote }}

  REDIS_HOST: {{ .Values.redis.host | quote }}
  REDIS_PORT: {{ .Values.redis.port | quote }}
```

**Secret** : comme un ConfigMap mais pour les donnees sensibles.
Les secrets sont encodé en base64 dans Kubernetes (pas chiffres !
pour le chiffrement, voir Sealed Secrets plus bas).

```yaml
# infra/k8s/helm/myapp/templates/secret.yaml

apiVersion: v1
kind: Secret
metadata:
  name: {{ include "myapp.fullname" . }}-secret
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
type: Opaque
stringData:
  APP_KEY: {{ .Values.app.key | quote }}
  DB_USERNAME: {{ .Values.database.username | quote }}
  DB_PASSWORD: {{ .Values.database.password | quote }}
  REDIS_PASSWORD: {{ .Values.redis.password | quote }}
```

**Deployment PHP** : c'est le cœur de l'application. Un Deployment dit
à Kubernetes "je veux N replicas de ce pod, avec cette image, ces variables
d'environnement, et ces limites de ressources".

Kubernetes s'assure en permanence que le bon nombre de pods tourne.
Si un pod plante, il en recréé un automatiquement.

```yaml
# infra/k8s/helm/myapp/templates/deployment-php.yaml

apiVersion: apps/v1
kind: Deployment
metadata:
  name: {{ include "myapp.fullname" . }}-php
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
    app.kubernetes.io/component: php
spec:
  # Si l'autoscaling est actif, c'est le HPA qui gere le nombre de replicas
  {{- if not .Values.autoscaling.enabled }}
  replicas: {{ .Values.replicaCount.php }}
  {{- end }}
  selector:
    matchLabels:
      {{- include "myapp.selectorLabels" . | nindent 6 }}
      app.kubernetes.io/component: php
  template:
    metadata:
      labels:
        {{- include "myapp.selectorLabels" . | nindent 8 }}
        app.kubernetes.io/component: php
      annotations:
        # Ces checksums forcent un redemarrage des pods quand la config change.
        # Sans ca, modifier un ConfigMap ne redemarrerait pas les pods
        # (ils garderaient les anciennes valeurs).
        checksum/config: {{ include (print $.Template.BasePath "/configmap.yaml") . | sha256sum }}
        checksum/secret: {{ include (print $.Template.BasePath "/secret.yaml") . | sha256sum }}
    spec:
      {{- with .Values.imagePullSecrets }}
      imagePullSecrets:
        {{- toYaml . | nindent 8 }}
      {{- end }}
      containers:
        - name: php
          image: "{{ .Values.image.php.repository }}:{{ .Values.image.php.tag }}"
          imagePullPolicy: {{ .Values.image.php.pullPolicy }}
          ports:
            - name: php-fpm
              containerPort: 9000
              protocol: TCP
          # envFrom injecte toutes les variables du ConfigMap et du Secret
          # dans l'environnement du conteneur
          envFrom:
            - configMapRef:
                name: {{ include "myapp.fullname" . }}-config
            - secretRef:
                name: {{ include "myapp.fullname" . }}-secret
          resources:
            {{- toYaml .Values.resources.php | nindent 12 }}
          # readinessProbe : K8s verifie que le pod est pret a recevoir du trafic.
          # Tant que la probe echoue, le pod ne recoit pas de requetes.
          readinessProbe:
            tcpSocket:
              port: php-fpm
            initialDelaySeconds: 5
            periodSeconds: 10
          # livenessProbe : K8s verifie que le pod est encore vivant.
          # Si la probe echoue, K8s tue et recree le pod.
          livenessProbe:
            tcpSocket:
              port: php-fpm
            initialDelaySeconds: 15
            periodSeconds: 20
          {{- if .Values.storage.enabled }}
          volumeMounts:
            - name: storage
              mountPath: /var/www/html/storage
          {{- end }}
      {{- if .Values.storage.enabled }}
      volumes:
        - name: storage
          persistentVolumeClaim:
            claimName: {{ include "myapp.fullname" . }}-storage
      {{- end }}
```

**Ingress** : expose l'application sur Internet. L'Ingress est un objet
Kubernetes qui dit "les requêtes pour myapp.com doivent aller vers le
service nginx sur le port 80". C'est le lien entre le nom de domaine
et les pods.

L'annotation `cert-manager.io/cluster-issuer` dit a cert-manager
de generer automatiquement un certificat Let's Encrypt pour ce domaine.

```yaml
# infra/k8s/helm/myapp/templates/ingress.yaml

{{- if .Values.ingress.enabled -}}
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: {{ include "myapp.fullname" . }}
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
  {{- with .Values.ingress.annotations }}
  annotations:
    {{- toYaml . | nindent 4 }}
  {{- end }}
spec:
  ingressClassName: {{ .Values.ingress.className }}
  {{- if .Values.ingress.tls }}
  tls:
    {{- range .Values.ingress.tls }}
    - hosts:
        {{- range .hosts }}
        - {{ . | quote }}
        {{- end }}
      secretName: {{ .secretName }}
    {{- end }}
  {{- end }}
  rules:
    {{- range .Values.ingress.hosts }}
    - host: {{ .host | quote }}
      http:
        paths:
          {{- range .paths }}
          - path: {{ .path }}
            pathType: {{ .pathType }}
            backend:
              service:
                name: {{ include "myapp.fullname" $ }}-nginx
                port:
                  number: 80
          {{- end }}
    {{- end }}
{{- end }}
```

**HPA (Horizontal Pod Autoscaler)** : ajuste automatiquement le nombre
de pods en fonction de la charge. Si le CPU depasse 70%, K8s ajoute des pods.
Si la charge baisse, il en retire (jusqu'au minimum).

> **Sur un VPS single-node**, l'autoscaling est limite par les ressources
> physiques du serveur. Si le VPS n'a que 4 GB de RAM, on ne peut pas
> avoir 10 pods de 512 MB chacun. Adapter le maxReplicas en consequence.

```yaml
# infra/k8s/helm/myapp/templates/hpa.yaml

{{- if .Values.autoscaling.enabled }}
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: {{ include "myapp.fullname" . }}-php
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: {{ include "myapp.fullname" . }}-php
  minReplicas: {{ .Values.autoscaling.minReplicas }}
  maxReplicas: {{ .Values.autoscaling.maxReplicas }}
  metrics:
    {{- if .Values.autoscaling.targetCPUUtilizationPercentage }}
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: {{ .Values.autoscaling.targetCPUUtilizationPercentage }}
    {{- end }}
    {{- if .Values.autoscaling.targetMemoryUtilizationPercentage }}
    - type: Resource
      resource:
        name: memory
        target:
          type: Utilization
          averageUtilization: {{ .Values.autoscaling.targetMemoryUtilizationPercentage }}
    {{- end }}
{{- end }}
```

**Job de migration** : un Job Kubernetes execute une commande une seule fois
puis se termine. Ici, on lance `php artisan migrate --force` apres chaque
déploiement. Les annotations `helm.sh/hook` disent a Helm d'executer ce job
automatiquement apres un `helm install` ou `helm upgrade`.

```yaml
# infra/k8s/helm/myapp/templates/job-migrate.yaml

{{- if .Values.migrations.enabled }}
apiVersion: batch/v1
kind: Job
metadata:
  name: {{ include "myapp.fullname" . }}-migrate-{{ .Release.Revision }}
  namespace: {{ .Values.namespace }}
  labels:
    {{- include "myapp.labels" . | nindent 4 }}
  annotations:
    # post-install = apres un premier déploiement
    # post-upgrade = apres une mise a jour
    "helm.sh/hook": post-install,post-upgrade
    "helm.sh/hook-weight": "-5"
    # Nettoie les anciens jobs avant d'en creer un nouveau
    "helm.sh/hook-delete-policy": before-hook-creation,hook-succeeded
spec:
  # Le job est automatiquement supprime 5 minutes apres sa fin
  ttlSecondsAfterFinished: 300
  template:
    spec:
      restartPolicy: Never
      containers:
        - name: migrate
          image: "{{ .Values.image.php.repository }}:{{ .Values.image.php.tag }}"
          command:
            - php
            - artisan
            - migrate
            - --force  # Necessaire en production (pas de confirmation interactive)
          envFrom:
            - configMapRef:
                name: {{ include "myapp.fullname" . }}-config
            - secretRef:
                name: {{ include "myapp.fullname" . }}-secret
  backoffLimit: 3  # Reessaie 3 fois en cas d'echec
{{- end }}
```

---

## 6.3 Cert-manager et HTTPS

### Comment ca fonctionne

Cert-manager est un composant Kubernetes qui gere automatiquement les
certificats TLS. Voici le flux :

1. On cree un `ClusterIssuer` qui dit "utilise Let's Encrypt"
2. Quand un Ingress a l'annotation `cert-manager.io/cluster-issuer`,
   cert-manager detecte automatiquement qu'il faut un certificat
3. Il cree un challenge HTTP (Let's Encrypt verifie qu'on controle le domaine)
4. Une fois valide, il cree un Secret Kubernetes avec le certificat
5. L'Ingress utilise ce Secret pour le HTTPS
6. Cert-manager renouvelle automatiquement avant expiration

> **Prerequis** : le domaine doit pointer vers l'IP du VPS (enregistrement DNS A).
> Sans ca, le challenge Let's Encrypt echouera.

### ClusterIssuer Let's Encrypt

On cree deux issuers : un pour le staging (tests, certificats invalides
mais sans rate limit) et un pour la production (vrais certificats).

**Toujours tester avec staging d'abord** pour eviter de se faire bloquer
par les rate limits de Let's Encrypt.

```yaml
# infra/k8s/manifests/cluster-issuer.yaml

apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: admin@myapp.com  # Email pour les notifications d'expiration
    privateKeySecretRef:
      name: letsencrypt-prod-key
    solvers:
      # http01 = Let's Encrypt verifie le domaine via une requete HTTP
      - http01:
          ingress:
            class: nginx

---
# Issuer de staging (pour les tests, pas de rate limit)
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-staging
spec:
  acme:
    server: https://acme-staging-v02.api.letsencrypt.org/directory
    email: admin@myapp.com
    privateKeySecretRef:
      name: letsencrypt-staging-key
    solvers:
      - http01:
          ingress:
            class: nginx
```

```bash
# Appliquer les issuers
kubectl apply -f infra/k8s/manifests/cluster-issuer.yaml

# Verifier qu'ils sont prets (READY doit etre True)
kubectl get clusterissuer
kubectl describe clusterissuer letsencrypt-prod
```

---

## 6.4 Sealed Secrets

### Le probleme

Les Secrets Kubernetes ne sont que encodes en base64, pas chiffres.
Si on les met dans Git, n'importe qui avec acces au repo peut les decoder.
Mais sans Git, on ne peut pas les versionner ni les reproduire.

### La solution : Sealed Secrets

Sealed Secrets chiffre les secrets avec une cle publique du cluster.
Le secret chiffre (SealedSecret) peut aller dans Git en toute securite.
Seul le cluster peut le dechiffrer avec sa cle privee.

**Le flux** :
1. On cree un Secret Kubernetes classique (en local, jamais commite)
2. On le chiffre avec `kubeseal` → SealedSecret
3. On commite le SealedSecret dans Git
4. Le controleur Sealed Secrets dans le cluster le dechiffre → Secret

### Installation

```bash
# Installer le controleur dans le cluster
helm repo add sealed-secrets https://bitnami-labs.github.io/sealed-secrets
helm install sealed-secrets sealed-secrets/sealed-secrets \
  --namespace kube-system

# Installer kubeseal (l'outil client, sur ta machine locale)
# Linux
wget https://github.com/bitnami-labs/sealed-secrets/releases/download/v0.24.0/kubeseal-0.24.0-linux-amd64.tar.gz
tar -xvf kubeseal-*.tar.gz
sudo mv kubeseal /usr/local/bin/

# macOS
brew install kubeseal
```

### Creer un Sealed Secret

```bash
# 1. Creer un Secret classique (--dry-run = ne l'envoie pas au cluster)
kubectl create secret generic myapp-secrets \
  --namespace production \
  --from-literal=APP_KEY="base64:xxxxx" \
  --from-literal=DB_PASSWORD="supersecret" \
  --dry-run=client -o yaml > secret.yaml

# 2. Le chiffrer avec kubeseal
kubeseal --format yaml < secret.yaml > sealed-secret.yaml

# 3. Le SealedSecret peut etre commite dans Git sans risque
# On peut supprimer le fichier secret.yaml (non chiffre)
rm secret.yaml

# 4. Appliquer le SealedSecret dans le cluster
# Le controleur le dechiffre automatiquement en Secret
kubectl apply -f sealed-secret.yaml
```

Le SealedSecret ressemble a ca (les valeurs sont chiffrees) :

```yaml
# Exemple de SealedSecret (genere par kubeseal)
apiVersion: bitnami.com/v1alpha1
kind: SealedSecret
metadata:
  name: myapp-secrets
  namespace: production
spec:
  encryptedData:
    APP_KEY: AgBxxxxxxx...  # Chiffre, illisible sans la cle du cluster
    DB_PASSWORD: AgByyyyyyy...
  template:
    metadata:
      name: myapp-secrets
      namespace: production
    type: Opaque
```

---

## 6.5 Deploiement avec Helm

### Premier déploiement

```bash
# Ajouter les repos Bitnami (pour les dependances PostgreSQL et Redis)
helm repo add bitnami https://charts.bitnami.com/bitnami
helm repo update

# Telecharger les dependances du chart
cd infra/k8s/helm/myapp
helm dependency update

# Deployer en production
# "upgrade --install" = installe si ca n'existe pas, met a jour sinon
helm upgrade --install myapp . \
  --namespace production \
  --create-namespace \
  -f values.yaml \
  -f values.production.yaml \
  --set app.key="base64:xxxxx" \
  --set database.password="dbpassword" \
  --set redis.password="redispassword" \
  --set postgresql.auth.password="dbpassword" \
  --set redis.auth.password="redispassword"
```

### Avec un fichier de secrets (recommande)

Plutot que de passer les secrets en ligne de commande (visible dans l'historique),
on les met dans un fichier non commite.

```yaml
# secrets.production.yaml (dans .gitignore, JAMAIS commite)
app:
  key: "base64:xxxxxxxxxxxxxxxxxxxxx"
database:
  password: "supersecretdbpassword"
redis:
  password: "supersecretredispassword"
postgresql:
  auth:
    password: "supersecretdbpassword"
redis:
  auth:
    password: "supersecretredispassword"
```

```bash
helm upgrade --install myapp . \
  --namespace production \
  --create-namespace \
  -f values.yaml \
  -f values.production.yaml \
  -f secrets.production.yaml
```

### Verifier le déploiement

```bash
# Voir la release Helm
helm list -n production
helm status myapp -n production

# Voir toutes les ressources creees
kubectl get all -n production

# Verifier que l'Ingress est configure et a une IP
kubectl get ingress -n production

# Verifier que le certificat est emis
kubectl get certificate -n production

# Voir les logs de l'app (en cas de probleme)
kubectl logs -l app.kubernetes.io/component=php -n production
kubectl logs -l app.kubernetes.io/component=nginx -n production

# Rollback si quelque chose ne va pas
helm rollback myapp -n production
```

---

## 6.6 Stockage persistant avec Longhorn

### Pourquoi Longhorn ?

K3s inclut un StorageClass `local-path` par defaut, qui stocke les donnees
sur le disque du noeud. Ca fonctionne pour un single-node, mais :

- Pas de snapshots
- Pas de backups intégrés
- Pas de replicas si on ajoute des nœuds plus tard

**Longhorn** est un système de stockage distribue pour Kubernetes qui offre
snapshots, backups et interface web. C'est optionnel pour un single-node
mais utile a long terme.

### Installation de Longhorn

```bash
# Prérequis sur le VPS
apt-get install -y open-iscsi nfs-common

# Installation via Helm
helm repo add longhorn https://charts.longhorn.io
helm repo update

helm install longhorn longhorn/longhorn \
  --namespace longhorn-system \
  --create-namespace \
  --set defaultSettings.defaultDataPath="/var/lib/longhorn"
```

```yaml
# Utiliser Longhorn dans le chart Laravel
# Dans values.yaml :
storage:
  enabled: true
  size: 10Gi
  storageClassName: longhorn
```

---

## 6.7 Network Policies

Les Network Policies sont les **regles de firewall internes** a Kubernetes.
Par defaut, tous les pods peuvent communiquer entre eux. Les Network Policies
restreignent ca au strict necessaire :

- PHP ne recoit du trafic **que depuis Nginx** (port 9000)
- PHP ne peut contacter **que PostgreSQL** (5432), **Redis** (6379), **DNS** (53) et **HTTPS externe** (443)
- Tout le reste est bloque

C'est le principe du **moindre privilege** : si un pod est compromis,
il ne peut pas scanner ni attaquer les autres services du cluster.

```yaml
# infra/k8s/helm/myapp/templates/network-policy.yaml

apiVersion: networking.k8s.io/v1
kind: NetworkPolicy
metadata:
  name: {{ include "myapp.fullname" . }}-php
  namespace: {{ .Values.namespace }}
spec:
  podSelector:
    matchLabels:
      {{- include "myapp.selectorLabels" . | nindent 6 }}
      app.kubernetes.io/component: php
  policyTypes:
    - Ingress
    - Egress
  ingress:
    # Autoriser le trafic entrant uniquement depuis les pods Nginx
    - from:
        - podSelector:
            matchLabels:
              app.kubernetes.io/component: nginx
      ports:
        - protocol: TCP
          port: 9000
  egress:
    # Autoriser vers PostgreSQL
    - to:
        - podSelector:
            matchLabels:
              app.kubernetes.io/name: postgresql
      ports:
        - protocol: TCP
          port: 5432
    # Autoriser vers Redis
    - to:
        - podSelector:
            matchLabels:
              app.kubernetes.io/name: redis
      ports:
        - protocol: TCP
          port: 6379
    # Autoriser la resolution DNS (indispensable)
    - to:
        - namespaceSelector: {}
          podSelector:
            matchLabels:
              k8s-app: kube-dns
      ports:
        - protocol: UDP
          port: 53
    # Autoriser HTTPS sortant (pour appeler des APIs externes)
    - to:
        - ipBlock:
            cidr: 0.0.0.0/0
      ports:
        - protocol: TCP
          port: 443
```

---

## 6.8 Script de déploiement complet

Ce script automatise le déploiement de bout en bout avec des verifications
a chaque etape. Il demande confirmation avant d'appliquer les changements.

```bash
#!/bin/bash
# infra/deploy.sh

set -e

ENVIRONMENT="${1:-production}"
VERSION="${2:-latest}"
NAMESPACE="${ENVIRONMENT}"

echo "=== Deploying $VERSION to $ENVIRONMENT ==="

# Verifier qu'on est bien connecte au bon cluster
CURRENT_CONTEXT=$(kubectl config current-context)
echo "Using kubectl context: $CURRENT_CONTEXT"
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Charger les secrets depuis un fichier ou les demander interactivement
if [ -f "k8s/helm/myapp/secrets.${ENVIRONMENT}.yaml" ]; then
    SECRETS_FILE="-f secrets.${ENVIRONMENT}.yaml"
else
    echo "Warning: No secrets file found. Using --set for secrets."
    read -sp "Enter APP_KEY: " APP_KEY
    echo
    read -sp "Enter DB_PASSWORD: " DB_PASSWORD
    echo
    read -sp "Enter REDIS_PASSWORD: " REDIS_PASSWORD
    echo
    SECRETS_ARGS="--set app.key=$APP_KEY --set database.password=$DB_PASSWORD --set redis.password=$REDIS_PASSWORD --set postgresql.auth.password=$DB_PASSWORD --set redis.auth.password=$REDIS_PASSWORD"
fi

cd k8s/helm/myapp

# Mettre a jour les dependances Helm (PostgreSQL, Redis)
echo ">>> Updating Helm dependencies..."
helm dependency update

# Dry-run : montre ce qui serait change sans rien appliquer
echo ">>> Running Helm dry-run..."
helm upgrade --install myapp . \
  --namespace $NAMESPACE \
  --create-namespace \
  -f values.yaml \
  -f values.${ENVIRONMENT}.yaml \
  ${SECRETS_FILE:-} \
  ${SECRETS_ARGS:-} \
  --set image.php.tag=$VERSION \
  --set image.nginx.tag=$VERSION \
  --dry-run

read -p "Apply changes? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Deployer pour de vrai
echo ">>> Deploying..."
helm upgrade --install myapp . \
  --namespace $NAMESPACE \
  --create-namespace \
  -f values.yaml \
  -f values.${ENVIRONMENT}.yaml \
  ${SECRETS_FILE:-} \
  ${SECRETS_ARGS:-} \
  --set image.php.tag=$VERSION \
  --set image.nginx.tag=$VERSION \
  --wait \
  --timeout 10m

# Verifier que les pods sont prets
echo ">>> Verifying deployment..."
kubectl rollout status deployment/myapp-php -n $NAMESPACE
kubectl rollout status deployment/myapp-nginx -n $NAMESPACE

# Health check
echo ">>> Running health check..."
INGRESS_HOST=$(kubectl get ingress myapp -n $NAMESPACE -o jsonpath='{.spec.rules[0].host}')
if curl -sf "https://${INGRESS_HOST}/health" > /dev/null; then
    echo "Health check passed!"
else
    echo "Health check failed!"
    exit 1
fi

echo "=== Deployment complete ==="
echo "Application: https://${INGRESS_HOST}"
```

---

## Checklist de fin de phase

- [ ] Helm chart complet avec templates (Deployment, Service, Ingress, etc.)
- [ ] Values separes par environnement (values.yaml, values.production.yaml)
- [ ] Cert-manager configure avec ClusterIssuer Let's Encrypt
- [ ] Certificat HTTPS automatique (verifier avec `kubectl get certificate`)
- [ ] Sealed Secrets pour les secrets dans Git
- [ ] HPA configure pour l'autoscaling (adapte aux ressources du VPS)
- [ ] Network Policies en place (moindre privilege)
- [ ] Job de migration automatique apres chaque déploiement
- [ ] CronJob pour le scheduler Laravel
- [ ] Script de déploiement fonctionnel avec dry-run et health check
- [ ] Application accessible en HTTPS sur le domaine
