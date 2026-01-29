# Parcours d'apprentissage DevOps

**Projet : Laravel + Inertia - Mono-repo - Local vers Production**
**Approche : Self-hosted sur VPS (sans AWS/GCP/Azure)**

Ce document est une version corrigée et enrichie d'un parcours proposé initialement.
Adapté pour une infrastructure self-hosted sur VPS avec Kubernetes léger (K3s).

---

## Sommaire détaillé

Chaque phase est documentée en détail dans un fichier séparé :

| Phase | Fichier                                                                     | Description                                         |
|-------|-----------------------------------------------------------------------------|-----------------------------------------------------|
| 0     | [00-apprendre-infrastructure.md](learning/00-apprendre-infrastructure.md)   | Explications de comment apprendre et tester         |
| 1     | [01-fondations.md](learning/01-fondations.md)                               | Documentation, tests, base applicative              |
| 2     | [02-containerisation.md](learning/02-containerisation.md)                   | Docker, CI basique, Dockerfile multi-stage          |
| 3     | [03-deploiement-vps.md](learning/03-deploiement-vps.md)                     | Provisioning VPS, Ansible, Docker Compose prod      |
| 4     | [04-kubernetes-local.md](learning/04-kubernetes-local.md)                   | K8s concepts, k3d, manifests, kubectl               |
| 5     | [05-infrastructure-as-code.md](learning/05-infrastructure-as-code.md)       | Pulumi, Ansible avancé, inventaires dynamiques      |
| 6     | [06-kubernetes-production.md](learning/06-kubernetes-production.md)         | Helm charts, cert-manager, Sealed Secrets, K3s prod |
| 7     | [07-operations.md](learning/07-operations.md)                               | Monitoring, CI/CD complet, sécurité, backups        |

---

## Vue d'ensemble

## Phase 1 : Fondations

### 1.1 Documentation

**Objectif** : Savoir expliquer le projet avant de le construire.

**A apprendre**

- Markdown (syntaxe, bonnes pratiques)
- Documentation orientée usage et décisions (ADR)

**A produire**

- `README.md` : vision, stack, lancement local
- `docs/architecture.md` : flux et composants
- `docs/decisions/` : ADR (Architecture Decision Records)

---

### 1.2 Base applicative saine

**Objectif** : Application portable, sans dépendance machine.

**A maitriser**

- Laravel : configuration, cache, environnements
- Inertia : build front, SSR optionnel
- Gestion des `.env` / `.env.example`
- Versioning Git : branching strategy (trunk-based ou GitFlow simplifie)

---

### 1.3 Tests

**Objectif** : Code fiable avant automatisation.

**A apprendre**

- Tests unitaires (PHPUnit)
- Tests d'integration (Feature tests Laravel)
- Tests E2E basiques (Playwright ou Cypress)
- Coverage et qualite de code (PHPStan, ESLint)

**A produire**

- Suite de tests minimale mais fonctionnelle
- Configuration des outils de qualite

> Les tests sont un prerequis au CI/CD, pas une option.

---

## Phase 2 : Containerisation

### 2.1 Docker (environnement local)

**Objectif** : Un clone, une commande, ca tourne.

**A apprendre**

- Dockerfile multi-stage (dev et prod)
- Docker Compose : services, volumes, reseaux
- Optimisation des images (cache layers, .dockerignore)

**Resultat attendu**

```bash
task up  # ou docker compose up
```

---

### 2.2 CI basique

**Objectif** : Validation automatique a chaque push.

**A mettre en place**

- Linting (PHP-CS-Fixer, ESLint)
- Tests automatises
- Build des assets

**Outils** : GitHub Actions, GitLab CI, ou Forgejo Actions (self-hosted)

> Le CI arrive tot dans le parcours car il valide tout ce qui suit.

---

### 2.3 Documentation d'exploitation

**Objectif** : Reprendre le projet sans memoire.

**A documenter**

- Demarrage / arret
- Rebuild des images
- Acces aux logs
- Erreurs courantes et solutions

**Fichier** : `docs/local-development.md`

---

## Phase 3 : Premier deploiement VPS

### 3.1 Provisioning du VPS

**Objectif** : Serveur pret a recevoir l'application.

**Providers recommandes** (avec bonnes APIs)

- Hetzner (excellent rapport qualite/prix)
- Scaleway
- OVH
- Contabo, Netcup (moins cher, APIs limitees)

**A apprendre**

- Choix du VPS (RAM, CPU, stockage)
- Configuration initiale (user non-root, sudo)
- Cles SSH (generation, deploiement, desactivation password)
- Firewall (ufw ou nftables)
- Fail2ban

---

### 3.2 Ansible - Fondamentaux

**Objectif** : Automatiser la configuration du VPS.

**A apprendre**

- Inventaires (statiques et dynamiques)
- Playbooks et tasks
- Roles (structure, reutilisabilite)
- Variables et templates Jinja2
- Idempotence
- Ansible Vault (secrets)

**Playbooks a creer**

- `bootstrap.yml` : configuration initiale (user, ssh, firewall)
- `docker.yml` : installation Docker
- `deploy.yml` : deploiement de l'application

> Ansible est central dans une approche self-hosted.

---

### 3.3 Deploiement Docker Compose sur VPS

**Objectif** : Application en production sans Kubernetes.

**A mettre en place**

- Reverse proxy (Caddy ou Traefik)
- Certificats SSL automatiques (Let's Encrypt)
- Docker Compose de production
- Volumes persistants (base de donnees)
- Backups automatises

**Resultat**

```bash
ansible-playbook -i inventory deploy.yml
# Application accessible en HTTPS
```

> Cette etape permet de livrer en production rapidement.
> Kubernetes viendra ensuite pour le scaling et la resilience.

---

## Phase 4 : Kubernetes local

### 4.1 Concepts de base

**Objectif** : Comprendre avant d'utiliser en production.

**A apprendre**

- Pod, Deployment, Service
- ConfigMap, Secret
- Namespaces
- PersistentVolume, PersistentVolumeClaim

**Outils locaux**

- k3d (K3s dans Docker) - recommande
- kind ou minikube
- kubectl
- k9s (interface terminal)

---

### 4.2 Deploiement Laravel sur K8s local

**Objectif** : Valider que l'application fonctionne sur K8s.

**A faire**

- Image Docker optimisee pour K8s
- Manifests YAML simples
- Service expose en local
- Ingress local

---

### 4.3 Helm

**Objectif** : Un chart, plusieurs environnements.

**A apprendre**

- Structure d'un chart
- `values.yaml` et surcharges par environnement
- Templates et helpers
- Gestion des releases

---

## Phase 5 : Infrastructure as Code

### 5.1 Pulumi ou OpenTofu (optionnel)

**Objectif** : Provisionner les VPS par le code.

**Quand l'utiliser**

- Si ton provider a une API/SDK (Hetzner, Scaleway, OVH)
- Pour gerer plusieurs VPS ou environnements
- Pour versionner l'infrastructure

**Providers disponibles**

- `pulumi-hcloud` (Hetzner)
- `pulumi-scaleway`
- OpenTofu avec providers Terraform

**A creer**

- VPS (taille, region, image)
- Reseau prive (si disponible)
- Firewall cloud
- DNS (si gere par le provider)

> Si tu n'as qu'un seul VPS, Ansible seul peut suffire.
> Pulumi devient interessant a partir de 2-3 machines ou pour du multi-env.

---

### 5.2 Ansible avance

**Objectif** : Gestion complete de l'infrastructure.

**A apprendre**

- Inventaires dynamiques (depuis Pulumi/API provider)
- Roles complexes et Galaxy
- Handlers et notifications
- Tests avec Molecule
- Integration avec le CI

**Roles a creer**

- `common` : securite de base, packages, users
- `docker` : installation et configuration
- `k3s` : installation du cluster
- `monitoring` : stack d'observabilite

---

## Phase 6 : Kubernetes en production (VPS)

### 6.1 Installation K3s

**Objectif** : Cluster Kubernetes leger sur VPS.

**Pourquoi K3s**

- Leger (binaire unique ~50MB)
- Inclut tout : containerd, Traefik, CoreDNS
- Parfait pour les VPS avec ressources limitees
- Production-ready

**Topologies possibles**

- Single node (1 VPS) : simple, suffisant pour debuter
- HA avec SQLite (3+ nodes) : resilience
- HA avec etcd externe : haute disponibilite complete

**A automatiser avec Ansible**

```bash
ansible-playbook -i inventory k3s.yml
# Cluster K3s operationnel
```

---

### 6.2 Composants essentiels

**Objectif** : Cluster pret pour la production.

**A installer**

- **Ingress** : Traefik (inclus) ou nginx-ingress
- **Cert-manager** : certificats Let's Encrypt automatiques
- **Longhorn** ou local-path : stockage persistant
- **Sealed Secrets** ou SOPS : gestion des secrets chiffres dans Git

**Registry Docker**

Options self-hosted :
- Harbor (complet mais lourd)
- Distribution Registry (leger)
- Utiliser le registry du provider CI (GitHub, GitLab)

---

### 6.3 Deploiement de l'application

**Objectif** : Laravel sur K3s en production.

**A deployer**

- Namespace dedie
- Deployment PHP-FPM + Nginx (ou image unique)
- Service et Ingress
- ConfigMaps et Secrets
- PVC pour stockage (si necessaire)
- Jobs pour migrations

**Avec Helm**

```bash
helm upgrade --install myapp ./helm/myapp \
  -f values.production.yaml \
  --namespace production
```

---

## Phase 7 : Operations et Maintenance

### 7.1 Monitoring et Observabilite

**Objectif** : Savoir ce qui se passe en production.

**Stack recommandee (legere)**

- **Prometheus** : metriques
- **Grafana** : dashboards
- **Loki** : logs (plus leger qu'ELK)
- **Alertmanager** : alertes vers email/Slack/Telegram

**Installation**

- Helm chart `kube-prometheus-stack`
- Ou via Ansible pour une stack Docker simple

**A monitorer**

- Ressources cluster (CPU, RAM, disque)
- Metriques applicatives
- Logs Laravel
- Certificats SSL (expiration)
- Disponibilite (uptime)

---

### 7.2 CI/CD complet

**Objectif** : Un `git push` vers production.

**Pipeline type**

1. Lint + Tests
2. Build image Docker
3. Push vers registry
4. Deploiement Helm/kubectl sur K3s
5. Smoke tests post-deploiement

**Connexion au cluster**

- Kubeconfig stocke en secret CI
- Ou runner self-hosted sur le VPS
- Ou Flux/ArgoCD pour du GitOps

**Options GitOps (recommande)**

- **Flux CD** : leger, natif Kubernetes
- **ArgoCD** : UI complete, plus lourd

> GitOps : le repo Git est la source de verite.
> Push sur main = deploiement automatique.

---

### 7.3 Securite

**Objectif** : Infrastructure et application securisees.

**Niveau VPS**

- SSH par cle uniquement
- Fail2ban actif
- Firewall restrictif
- Updates automatiques (unattended-upgrades)
- Audit des connexions

**Niveau Kubernetes**

- RBAC configure
- Network Policies
- Pod Security Standards
- Secrets chiffres (Sealed Secrets)
- Scan d'images (Trivy)

**Niveau Application**

- Headers de securite (CSP, HSTS)
- CORS configure
- Rate limiting
- Validation des inputs

---

### 7.4 Backup et Disaster Recovery

**Objectif** : Pouvoir restaurer en cas de probleme.

**A sauvegarder**

- Base de donnees (dump regulier)
- Volumes persistants
- Secrets et ConfigMaps
- Configuration Ansible/Pulumi

**Outils**

- Velero (backup K8s complet)
- Scripts cron + rclone vers stockage externe
- Snapshots VPS (si disponible chez le provider)

**A documenter**

- `docs/runbook.md` : procedures de restauration
- RTO/RPO definis

---

## Structure recommandee du mono-repo

```
project/
├── app/                      # Laravel + Inertia
│   ├── tests/
│   └── ...
├── infra/
│   ├── docker/              # Dockerfiles
│   ├── compose/             # docker-compose (local + prod simple)
│   ├── ansible/
│   │   ├── inventory/
│   │   ├── roles/
│   │   ├── playbooks/
│   │   └── ansible.cfg
│   ├── k8s/
│   │   ├── manifests/       # YAML bruts (apprentissage)
│   │   └── helm/            # Charts Helm
│   └── pulumi/              # Si utilise
├── .github/
│   └── workflows/           # CI/CD
├── docs/
│   ├── architecture.md
│   ├── local-development.md
│   ├── deployment.md
│   ├── runbook.md
│   └── decisions/
├── docker-compose.yml       # Pour le dev local
├── Taskfile.yml
└── README.md
```

---

## Resume : approche Self-hosted vs Cloud manage

| Aspect | Cloud manage (AWS/GCP) | Self-hosted (VPS) |
|--------|------------------------|-------------------|
| Kubernetes | EKS, GKE, AKS | K3s sur VPS |
| Provisioning | Pulumi/Terraform | Ansible (+ Pulumi optionnel) |
| Secrets | External Secrets + cloud | Sealed Secrets, SOPS, Vault |
| Registry | ECR, GCR, ACR | Harbor, registry CI, Docker Hub |
| Monitoring | Services manages | Prometheus + Grafana self-hosted |
| Cout | Variable, peut exploser | Fixe, previsible |
| Controle | Limite | Total |
| Maintenance | Provider | Toi |

---

## Competences acquises a la fin du parcours

- Provisionner et securiser un VPS from scratch
- Automatiser avec Ansible
- Deployer avec Docker Compose puis Kubernetes
- Installer et gerer un cluster K3s
- Mettre en place du CI/CD complet
- Monitorer et debugger en production
- Gerer les backups et la securite
- Etre autonome sans dependre des cloud providers

---

## Ressources recommandees

**Kubernetes / K3s**

- [K3s Documentation](https://docs.k3s.io/)
- [Kubernetes Documentation](https://kubernetes.io/docs/home/)
- [k3d - K3s in Docker](https://k3d.io/)

**Ansible**

- [Ansible Documentation](https://docs.ansible.com/)
- [Ansible for DevOps (livre)](https://www.ansiblefordevops.com/)

**Self-hosting**

- [Awesome Self-Hosted](https://github.com/awesome-selfhosted/awesome-selfhosted)
- [12 Factor App](https://12factor.net/fr/)

**Securite**

- [CIS Benchmarks](https://www.cisecurity.org/cis-benchmarks)
- [Kubernetes Security Best Practices](https://kubernetes.io/docs/concepts/security/)
