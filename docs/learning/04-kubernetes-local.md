# Phase 4 : Kubernetes local

Cette phase introduit les concepts Kubernetes en environnement local
avant de passer à la production.

---

## 4.1 Pourquoi Kubernetes

### Problèmes résolus par K8s

Docker Compose fonctionne bien pour un seul serveur, mais montre ses limites quand
tu as besoin de haute disponibilité, de scaling automatique, ou de déploiements sans
interruption. Kubernetes résout ces problèmes au prix d'une complexité supplémentaire.

| Problème          | Solution Docker Compose | Solution Kubernetes          |
|-------------------|-------------------------|------------------------------|
| Scaling           | Manuel, limité          | Automatique (HPA)            |
| Haute dispo       | Aucune                  | Multi-replicas, self-healing |
| Rolling updates   | Downtime                | Zero-downtime                |
| Config management | Fichiers .env           | ConfigMaps, Secrets          |
| Service discovery | Links/networks          | DNS intégré                  |
| Load balancing    | Externe (Nginx)         | Intégré (Service)            |

### Quand NE PAS utiliser K8s

- Application simple, trafic faible
- Équipe sans compétences K8s
- Budget limité (overhead de gestion)
- Pas besoin de scaling

> Docker Compose sur VPS reste viable pour beaucoup de projets.

**Pour ton VPS Hostinger** : si ton app reçoit peu de trafic, Docker Compose en prod
(phase 3) est suffisant. K8s devient intéressant quand tu veux du zero-downtime
deployment, du self-healing (redémarrage automatique des containers crashés), ou du
scaling horizontal. Même sur un seul VPS, K3s apporte ces bénéfices avec un overhead
minimal (~512 MB de RAM).

---

## 4.2 Concepts fondamentaux

### Architecture Kubernetes

Un cluster K8s est divisé en deux parties :

- Le **Control Plane** : le "cerveau" qui décide où et comment lancer les containers
- Les **Nodes** : les machines qui exécutent réellement les containers

```
┌─────────────────────────────────────────────────────────────┐
│                    Control Plane                            │
│   ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐   │
│   │ API Server  │ │   etcd      │ │ Controller Manager  │   │
│   └──────┬──────┘ └─────────────┘ └─────────────────────┘   │
│          │                                                  │
│   ┌──────┴──────┐                                           │
│   │  Scheduler  │                                           │
│   └─────────────┘                                           │
└─────────────────────────────────────────────────────────────┘
                              │
         ┌────────────────────┼────────────────────┐
         ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│     Node 1      │  │     Node 2      │  │     Node 3      │
│  ┌───────────┐  │  │  ┌───────────┐  │  │  ┌───────────┐  │
│  │  kubelet  │  │  │  │  kubelet  │  │  │  │  kubelet  │  │
│  └───────────┘  │  │  └───────────┘  │  │  └───────────┘  │
│  ┌───────────┐  │  │  ┌───────────┐  │  │  ┌───────────┐  │
│  │ kube-proxy│  │  │  │ kube-proxy│  │  │  │ kube-proxy│  │
│  └───────────┘  │  │  └───────────┘  │  │  └───────────┘  │
│  ┌─────┐┌─────┐ │  │  ┌─────┐┌─────┐ │  │  ┌─────┐        │
│  │ Pod ││ Pod │ │  │  │ Pod ││ Pod │ │  │  │ Pod │        │
│  └─────┘└─────┘ │  │  └─────┘└─────┘ │  │  └─────┘        │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

**Pourquoi cette séparation ?**

- **API Server** : le point d'entrée unique. `kubectl` parle à l'API Server, jamais directement aux nodes. Ça centralise
  l'authentification et l'autorisation
- **etcd** : base de données clé-valeur qui stocke l'état complet du cluster. Si tu perds etcd, tu perds ton cluster
- **Scheduler** : décide sur quel node placer un nouveau Pod, en fonction des ressources disponibles
- **Controller Manager** : boucle de contrôle qui s'assure que l'état réel correspond à l'état désiré (ex : "je veux 3
  replicas" → il vérifie en permanence qu'il y en a bien 3)
- **kubelet** : agent sur chaque node qui reçoit les ordres de l'API Server et gère les containers localement
- **kube-proxy** : gère le réseau sur chaque node pour que les Services fonctionnent

### Objets principaux

| Objet                | Description                      | Analogie Docker     |
|----------------------|----------------------------------|---------------------|
| **Pod**              | Plus petite unité, 1+ containers | Container           |
| **Deployment**       | Gère les replicas de Pods        | -                   |
| **Service**          | Expose les Pods, load balancing  | Port mapping        |
| **ConfigMap**        | Configuration non-sensible       | .env                |
| **Secret**           | Données sensibles (chiffrées)    | .env (secrets)      |
| **Ingress**          | Routage HTTP externe             | Nginx reverse proxy |
| **PersistentVolume** | Stockage persistant              | Volume              |
| **Namespace**        | Isolation logique                | -                   |

**Pourquoi autant d'objets différents ?** En Docker Compose, tout est dans un seul
fichier `docker-compose.yml`. K8s découpe chaque responsabilité en un objet séparé.
C'est plus verbeux, mais ça permet de modifier la config sans toucher au déploiement,
de scaler les pods sans recréer les volumes, etc. Chaque objet a son cycle de vie
propre.

---

## 4.3 Installation locale

### Option 1 : k3d (recommandé)

K3d exécute K3s (Kubernetes léger) à l'intérieur de containers Docker.
C'est le choix recommandé parce que :

- **C'est ce que tu utiliseras en prod** : K3s sur ton VPS Hostinger, donc autant s'entraîner avec
- **Léger** : pas de VM, juste des containers Docker
- **Rapide** : un cluster se crée en quelques secondes
- **Multi-node simulé** : tu peux tester avec 1 server + 2 agents même sur ta machine

```bash
# Installation
curl -s https://raw.githubusercontent.com/k3d-io/k3d/main/install.sh | bash

# Créer un cluster
k3d cluster create dev \
  --servers 1 \
  --agents 2 \
  --port "80:80@loadbalancer" \
  --port "443:443@loadbalancer"

# Vérifier
kubectl get nodes

# Supprimer
k3d cluster delete dev
```

**Détail des options :**

- `--servers 1` : 1 node control plane. En local, un seul suffit
- `--agents 2` : 2 nodes workers qui exécuteront tes pods. Ça simule un vrai cluster multi-node
- `--port "80:80@loadbalancer"` : mappe le port 80 de ta machine vers le load balancer de k3d, pour que
  `http://localhost` atteigne ton cluster

### Option 2 : kind

Kubernetes in Docker. Plus proche du K8s "vanilla" mais plus lourd que k3d.
Utile si tu veux tester un comportement spécifique à K8s standard (pas K3s).

```bash
# Installation
go install sigs.k8s.io/kind@latest
# ou
brew install kind

# Créer un cluster
cat <<EOF | kind create cluster --config=-
kind: Cluster
apiVersion: kind.x-k8s.io/v1alpha4
nodes:
  - role: control-plane
    extraPortMappings:
      - containerPort: 80
        hostPort: 80
      - containerPort: 443
        hostPort: 443
  - role: worker
  - role: worker
EOF

# Vérifier
kubectl cluster-info
```

### Option 3 : minikube

Le plus ancien, utilise une VM complète. Plus lourd, mais offre des addons intégrés
(dashboard, ingress, metrics-server) qui simplifient l'apprentissage. Moins
représentatif de ta future prod K3s.

```bash
# Installation
brew install minikube

# Créer un cluster
minikube start --cpus=4 --memory=4096

# Activer les addons utiles
minikube addons enable ingress
minikube addons enable metrics-server

# Dashboard
minikube dashboard
```

### kubectl

`kubectl` est le CLI pour interagir avec n'importe quel cluster K8s. C'est ton outil
principal -- tout passe par lui : déployer, debugger, scaler, lire les logs.

```bash
# Installation
brew install kubectl
# ou
curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"

# Autocomplétion (indispensable, les commandes sont longues)
echo 'source <(kubectl completion bash)' >> ~/.bashrc
echo 'alias k=kubectl' >> ~/.bashrc
echo 'complete -o default -F __start_kubectl k' >> ~/.bashrc
```

### k9s (interface terminal)

`k9s` est un TUI (Terminal User Interface) pour K8s. Au lieu de taper des commandes
`kubectl get pods` en boucle, tu as une vue en temps réel de ton cluster. Très utile
pour le debug.

```bash
# Installation
brew install k9s

# Lancer
k9s

# Raccourcis utiles
# :pods     - lister les pods
# :deploy   - lister les deployments
# :svc      - lister les services
# :logs     - voir les logs
# d         - décrire la ressource
# l         - logs
# s         - shell dans le container
# ctrl+d    - supprimer
```

---

## 4.4 Premiers manifests

En K8s, on décrit ce qu'on veut dans des fichiers YAML appelés "manifests".
On les applique avec `kubectl apply -f fichier.yaml` et K8s se charge de rendre
l'état réel conforme à ce qui est décrit.

### Namespace

Un namespace est un "dossier virtuel" dans ton cluster. Il isole les ressources
pour éviter les collisions de noms et permettre des politiques de sécurité différentes
par environnement (dev, staging, prod).

```yaml
# infra/k8s/manifests/namespace.yaml

apiVersion: v1
kind: Namespace
metadata:
  name: myapp
  labels:
    app: myapp
    environment: development
```

**Pourquoi ?** Sans namespace, toutes tes ressources atterrissent dans `default`.
Quand tu auras PostgreSQL, Redis, PHP, Nginx, des Jobs, des CronJobs... ça devient
vite le bazar. Le namespace regroupe tout ce qui concerne ton app et permet de tout
supprimer d'un coup avec `kubectl delete namespace myapp`.

### ConfigMap

Un ConfigMap stocke de la configuration non-sensible sous forme de paires clé-valeur.
C'est l'équivalent K8s de ton fichier `.env`, mais sans les secrets.

```yaml
# infra/k8s/manifests/configmap.yaml

apiVersion: v1
kind: ConfigMap
metadata:
  name: myapp-config
  namespace: myapp
data:
  APP_NAME: "My Application"
  APP_ENV: "local"
  APP_DEBUG: "true"
  APP_URL: "http://localhost"

  DB_CONNECTION: "pgsql"
  DB_HOST: "postgres"
  DB_PORT: "5432"
  DB_DATABASE: "app"

  CACHE_DRIVER: "redis"
  SESSION_DRIVER: "redis"
  QUEUE_CONNECTION: "redis"

  REDIS_HOST: "redis"
  REDIS_PORT: "6379"
```

**Pourquoi séparer config et secrets ?** Pour deux raisons :

1. **Sécurité** : les ConfigMaps sont visibles par tous ceux qui ont accès au namespace. Les Secrets ont des contrôles
   d'accès plus stricts
2. **Cycle de vie** : tu modifies ta config (changer `APP_DEBUG`) bien plus souvent que tes mots de passe. Séparer
   permet de mettre à jour l'un sans toucher l'autre

**Pourquoi `DB_HOST: "postgres"` ?** En K8s, chaque Service crée une entrée DNS
interne. Si tu crées un Service nommé `postgres`, tous les pods du namespace peuvent
le résoudre par son nom, comme en Docker Compose.

### Secret

Un Secret est comme un ConfigMap, mais pour les données sensibles. Les valeurs sont
encodées en base64 (pas chiffré -- c'est juste un encodage). Le vrai chiffrement
viendra en phase 6 avec Sealed Secrets.

```yaml
# infra/k8s/manifests/secret.yaml

apiVersion: v1
kind: Secret
metadata:
  name: myapp-secret
  namespace: myapp
type: Opaque
stringData: # Sera encodé en base64 automatiquement
  APP_KEY: "base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  DB_USERNAME: "app"
  DB_PASSWORD: "secretpassword"
  REDIS_PASSWORD: "redispassword"
```

> **Attention** : ce fichier contient des mots de passe en clair. Ne le commite
> **jamais** dans Git tel quel. En production, on utilisera Sealed Secrets (phase 6)
> pour chiffrer ces valeurs avant de les stocker dans le repo.

`type: Opaque` signifie "secret générique". K8s a d'autres types (`kubernetes.io/tls`
pour les certificats, `kubernetes.io/dockerconfigjson` pour les credentials de
registry), mais `Opaque` couvre la plupart des cas.

### Deployment PHP

Le Deployment est l'objet central de K8s. Il décrit **quel container lancer, combien
d'instances, avec quelles ressources, et comment vérifier qu'il fonctionne**.

```yaml
# infra/k8s/manifests/deployment-php.yaml

apiVersion: apps/v1
kind: Deployment
metadata:
  name: myapp-php
  namespace: myapp
  labels:
    app: myapp
    component: php
spec:
  replicas: 2
  selector:
    matchLabels:
      app: myapp
      component: php
  template:
    metadata:
      labels:
        app: myapp
        component: php
    spec:
      containers:
        - name: php
          image: myapp-php:latest
          imagePullPolicy: IfNotPresent
          ports:
            - containerPort: 9000
              name: php-fpm
          envFrom:
            - configMapRef:
                name: myapp-config
            - secretRef:
                name: myapp-secret
          resources:
            requests:
              memory: "128Mi"
              cpu: "100m"
            limits:
              memory: "512Mi"
              cpu: "500m"
          readinessProbe:
            tcpSocket:
              port: 9000
            initialDelaySeconds: 5
            periodSeconds: 10
          livenessProbe:
            tcpSocket:
              port: 9000
            initialDelaySeconds: 15
            periodSeconds: 20
          volumeMounts:
            - name: storage
              mountPath: /var/www/html/storage
      volumes:
        - name: storage
          persistentVolumeClaim:
            claimName: myapp-storage
```

**Explication des champs importants :**

- **`replicas: 2`** : K8s maintiendra toujours 2 pods PHP actifs. Si l'un crashe, un
  nouveau est créé automatiquement (self-healing). En Docker Compose, un container
  mort reste mort jusqu'à intervention manuelle

- **`selector.matchLabels`** : c'est le lien entre le Deployment et ses Pods. K8s
  utilise les labels (pas les noms) pour savoir quels pods lui appartiennent. Le
  `template.metadata.labels` doit correspondre au `selector.matchLabels`

- **`envFrom`** : injecte toutes les variables du ConfigMap et du Secret comme
  variables d'environnement dans le container. Laravel les lira comme un `.env` normal

- **`resources.requests`** : le minimum de ressources garanti à ce pod. Le Scheduler
  utilise cette valeur pour décider sur quel node le placer. `100m` = 100 millicores
  = 10% d'un CPU

- **`resources.limits`** : le maximum que le pod peut consommer. S'il dépasse la
  limite mémoire, K8s le tue (OOMKilled). S'il dépasse le CPU, il est throttlé (ralenti)

- **`readinessProbe`** : K8s vérifie que le pod est prêt à recevoir du trafic. Tant
  que cette probe échoue, le Service n'envoie pas de requêtes vers ce pod. Ça évite
  d'envoyer du trafic à un pod qui démarre encore

- **`livenessProbe`** : K8s vérifie que le pod est toujours vivant. Si cette probe
  échoue plusieurs fois, K8s redémarre le pod. `initialDelaySeconds: 15` laisse le
  temps à PHP-FPM de démarrer avant de commencer les vérifications

- **`imagePullPolicy: IfNotPresent`** : en local avec k3d, les images sont importées
  manuellement. `IfNotPresent` évite que K8s essaie de les télécharger depuis un
  registry distant

### Deployment Nginx

Nginx est séparé de PHP dans son propre Deployment. Ça permet de les scaler
indépendamment : si ton bottleneck est PHP, tu peux passer à 4 replicas PHP
tout en gardant 2 replicas Nginx.

```yaml
# infra/k8s/manifests/deployment-nginx.yaml

apiVersion: apps/v1
kind: Deployment
metadata:
  name: myapp-nginx
  namespace: myapp
  labels:
    app: myapp
    component: nginx
spec:
  replicas: 2
  selector:
    matchLabels:
      app: myapp
      component: nginx
  template:
    metadata:
      labels:
        app: myapp
        component: nginx
    spec:
      containers:
        - name: nginx
          image: myapp-nginx:latest
          imagePullPolicy: IfNotPresent
          ports:
            - containerPort: 80
              name: http
          resources:
            requests:
              memory: "64Mi"
              cpu: "50m"
            limits:
              memory: "128Mi"
              cpu: "200m"
          readinessProbe:
            httpGet:
              path: /health
              port: 80
            initialDelaySeconds: 5
            periodSeconds: 10
          livenessProbe:
            httpGet:
              path: /health
              port: 80
            initialDelaySeconds: 10
            periodSeconds: 20
```

**Pourquoi Nginx utilise `httpGet` pour ses probes alors que PHP utilise `tcpSocket` ?**
Nginx peut répondre à des requêtes HTTP (on a notre endpoint `/health` dans la config),
donc on peut vérifier qu'il fonctionne réellement. PHP-FPM parle le protocole FastCGI
(pas HTTP), donc on se contente de vérifier que le port TCP 9000 est ouvert.

### Service

Un Service donne une adresse réseau stable à un groupe de pods. Les pods sont
éphémères (ils sont créés et détruits constamment), mais un Service garde toujours
la même adresse IP interne et le même nom DNS.

```yaml
# infra/k8s/manifests/service.yaml

apiVersion: v1
kind: Service
metadata:
  name: myapp-php
  namespace: myapp
spec:
  selector:
    app: myapp
    component: php
  ports:
    - port: 9000
      targetPort: 9000
      name: php-fpm
  type: ClusterIP

---
apiVersion: v1
kind: Service
metadata:
  name: myapp-nginx
  namespace: myapp
spec:
  selector:
    app: myapp
    component: nginx
  ports:
    - port: 80
      targetPort: 80
      name: http
  type: ClusterIP
```

**Pourquoi `type: ClusterIP` ?** Il existe 3 types de Services :

- **ClusterIP** (défaut) : accessible uniquement depuis l'intérieur du cluster. C'est
  ce qu'on veut pour PHP et Nginx, car le trafic externe passera par l'Ingress
- **NodePort** : expose un port sur chaque node. Utile pour du debug, pas pour la prod
- **LoadBalancer** : crée un load balancer externe (chez un cloud provider). Sur un VPS,
  on n'utilise pas ça, c'est l'Ingress qui joue ce rôle

**Comment le Service sait vers quels pods envoyer le trafic ?** Via le `selector`.
Tous les pods qui ont les labels `app: myapp` et `component: php` recevront du trafic
du service `myapp-php`. Si tu as 2 replicas, le Service répartit automatiquement les
requêtes entre les deux (round-robin).

### Ingress

L'Ingress est le point d'entrée HTTP/HTTPS de ton cluster. C'est l'équivalent de
la config Nginx qu'on avait dans `infra/nginx/default.conf`, mais gérée par K8s.
Il route le trafic externe vers les bons Services en fonction du hostname et du path.

```yaml
# infra/k8s/manifests/ingress.yaml

apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: myapp-ingress
  namespace: myapp
  annotations:
    # Pour k3s/traefik
    traefik.ingress.kubernetes.io/router.entrypoints: web
spec:
  rules:
    - host: myapp.localhost
      http:
        paths:
          - path: /
            pathType: Prefix
            backend:
              service:
                name: myapp-nginx
                port:
                  number: 80
```

**Pourquoi un Ingress et pas directement un Service NodePort/LoadBalancer ?**
L'Ingress permet :

- Le routage basé sur le hostname (`api.myapp.com` → service A, `myapp.com` → service B)
- La terminaison TLS (HTTPS) au même endroit
- La gestion centralisée des certificats SSL (avec cert-manager en phase 6)
- Un seul point d'entrée pour tout le cluster

**L'annotation `traefik`** : K3s embarque Traefik comme Ingress Controller par défaut.
Cette annotation lui dit d'écouter sur l'entrypoint `web` (port 80). En prod,
on pourra remplacer Traefik par nginx-ingress si besoin.

### PersistentVolumeClaim

Un PVC est une demande de stockage. En K8s, les pods sont éphémères : quand un pod
meurt, ses fichiers disparaissent. Un PVC demande au cluster de fournir un espace
de stockage persistant qui survit aux redémarrages des pods.

```yaml
# infra/k8s/manifests/pvc.yaml

apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: myapp-storage
  namespace: myapp
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 1Gi
  # storageClassName: local-path  # Pour k3s
```

**Pourquoi ?** Laravel écrit dans `storage/` (logs, cache de vues, fichiers uploadés).
Sans PVC, à chaque redémarrage du pod, tout serait perdu.

- **`ReadWriteOnce`** : le volume peut être monté en lecture-écriture par un seul node
  à la fois. C'est suffisant pour la plupart des cas. `ReadWriteMany` (plusieurs nodes)
  nécessite un système de fichiers réseau (NFS, Longhorn)
- **`storage: 1Gi`** : taille demandée. En local, le StorageClass `local-path` de K3s
  utilise le disque du node. Sur ton VPS Hostinger, ce sera le disque du serveur

---

## 4.5 Base de données et Redis

### PostgreSQL

Déployer PostgreSQL dans K8s en local permet de tester l'ensemble de la stack sans
dépendances externes. En production, tu pourras garder PostgreSQL dans K8s ou utiliser
un service managé selon tes besoins.

```yaml
# infra/k8s/manifests/postgres.yaml

apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: postgres-data
  namespace: myapp
spec:
  accessModes:
    - ReadWriteOnce
  resources:
    requests:
      storage: 5Gi

---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: postgres
  namespace: myapp
spec:
  replicas: 1
  selector:
    matchLabels:
      app: postgres
  template:
    metadata:
      labels:
        app: postgres
    spec:
      containers:
        - name: postgres
          image: postgres:16-alpine
          ports:
            - containerPort: 5432
          env:
            - name: POSTGRES_DB
              valueFrom:
                configMapKeyRef:
                  name: myapp-config
                  key: DB_DATABASE
            - name: POSTGRES_USER
              valueFrom:
                secretKeyRef:
                  name: myapp-secret
                  key: DB_USERNAME
            - name: POSTGRES_PASSWORD
              valueFrom:
                secretKeyRef:
                  name: myapp-secret
                  key: DB_PASSWORD
          volumeMounts:
            - name: data
              mountPath: /var/lib/postgresql/data
          resources:
            requests:
              memory: "256Mi"
              cpu: "100m"
            limits:
              memory: "512Mi"
              cpu: "500m"
          readinessProbe:
            exec:
              command:
                - pg_isready
                - -U
                - app
            initialDelaySeconds: 5
            periodSeconds: 10
      volumes:
        - name: data
          persistentVolumeClaim:
            claimName: postgres-data

---
apiVersion: v1
kind: Service
metadata:
  name: postgres
  namespace: myapp
spec:
  selector:
    app: postgres
  ports:
    - port: 5432
      targetPort: 5432
  type: ClusterIP
```

**Détails importants :**

- **`replicas: 1`** : une seule instance de PostgreSQL. Les bases de données ne se
  scalent pas comme les serveurs web -- la réplication PostgreSQL est un sujet complexe
  qu'on ne traite pas ici.
- **`valueFrom: configMapKeyRef / secretKeyRef`** : au lieu de mettre les valeurs en
  dur, on les tire du ConfigMap et du Secret. C'est le même principe que le `envFrom`
  du déploiement PHP, mais variable par variable. Ça permet de n'injecter que les
  variables dont PostgreSQL a besoin

- **`readinessProbe: exec: pg_isready`** : `pg_isready` est un outil fourni par
  PostgreSQL qui vérifie que la base accepte les connexions. Plus fiable qu'un simple
  check TCP

- **Le PVC séparé** : les données PostgreSQL survivent aux redémarrages du pod grâce
  au PVC `postgres-data`. Même si le pod est supprimé et recréé, les données restent

### Redis

Redis sert de cache, de store de sessions, et de broker de queues pour Laravel.
Il est rapide car il stocke tout en mémoire.

```yaml
# infra/k8s/manifests/redis.yaml

apiVersion: apps/v1
kind: Deployment
metadata:
  name: redis
  namespace: myapp
spec:
  replicas: 1
  selector:
    matchLabels:
      app: redis
  template:
    metadata:
      labels:
        app: redis
    spec:
      containers:
        - name: redis
          image: redis:7-alpine
          command:
            - redis-server
            - --appendonly
            - "yes"
            - --requirepass
            - $(REDIS_PASSWORD)
          ports:
            - containerPort: 6379
          env:
            - name: REDIS_PASSWORD
              valueFrom:
                secretKeyRef:
                  name: myapp-secret
                  key: REDIS_PASSWORD
          resources:
            requests:
              memory: "64Mi"
              cpu: "50m"
            limits:
              memory: "128Mi"
              cpu: "200m"
          readinessProbe:
            exec:
              command:
                - redis-cli
                - ping
            initialDelaySeconds: 5
            periodSeconds: 10

---
apiVersion: v1
kind: Service
metadata:
  name: redis
  namespace: myapp
spec:
  selector:
    app: redis
  ports:
    - port: 6379
      targetPort: 6379
  type: ClusterIP
```

**Pourquoi `--appendonly yes` ?** Par défaut, Redis ne persiste rien sur disque.
`--appendonly yes` active l'AOF (Append Only File) : chaque écriture est journalisée
sur disque. Si Redis redémarre, il rejoue le journal pour retrouver son état. Sans ça,
un redémarrage du pod = perte de toutes les sessions, du cache, et des jobs en queue.

**Pourquoi `--requirepass` ?** Même dans un cluster K8s, un mot de passe Redis est
une bonne pratique. Les Network Policies (phase 6) restreindront l'accès au niveau
réseau, mais le mot de passe ajoute une couche de défense supplémentaire.

---

## 4.6 Jobs et migrations

### Job de migration

Un Job est un pod qui s'exécute une fois et se termine. C'est l'outil parfait
pour les migrations de base de données : tu veux exécuter `php artisan migrate`
une seule fois à chaque déploiement, pas en permanence.

```yaml
# infra/k8s/manifests/job-migrate.yaml

apiVersion: batch/v1
kind: Job
metadata:
  name: myapp-migrate
  namespace: myapp
spec:
  ttlSecondsAfterFinished: 300  # Supprimé après 5 minutes
  template:
    spec:
      restartPolicy: Never
      containers:
        - name: migrate
          image: myapp-php:latest
          command:
            - php
            - artisan
            - migrate
            - --force
          envFrom:
            - configMapRef:
                name: myapp-config
            - secretRef:
                name: myapp-secret
  backoffLimit: 3
```

**Pourquoi un Job et pas un `exec` dans le container PHP ?**

- Le Job est **déclaratif** : il fait partie de ton manifest, versionné dans Git
- **`backoffLimit: 3`** : si la migration échoue, K8s réessaie jusqu'à 3 fois
- **`ttlSecondsAfterFinished: 300`** : le pod du Job est nettoyé automatiquement
  après 5 minutes, pour ne pas encombrer le cluster
- **`restartPolicy: Never`** : contrairement à un Deployment, on ne veut pas que
  le pod redémarre en boucle. S'il échoue, c'est le `backoffLimit` qui gère les retries
- **`--force`** : nécessaire en production, Laravel refuse de migrer sans ce flag
  pour éviter les accidents

### CronJob pour les tâches planifiées

Laravel a un scheduler (`php artisan schedule:run`) qui doit être exécuté chaque
minute. En Docker Compose, on mettrait un cron dans le container. En K8s, on utilise
un CronJob : K8s crée un pod chaque minute, exécute la commande, et le supprime.

```yaml
# infra/k8s/manifests/cronjob-scheduler.yaml

apiVersion: batch/v1
kind: CronJob
metadata:
  name: myapp-scheduler
  namespace: myapp
spec:
  schedule: "* * * * *"  # Chaque minute
  concurrencyPolicy: Forbid
  jobTemplate:
    spec:
      template:
        spec:
          restartPolicy: OnFailure
          containers:
            - name: scheduler
              image: myapp-php:latest
              command:
                - php
                - artisan
                - schedule:run
              envFrom:
                - configMapRef:
                    name: myapp-config
                - secretRef:
                    name: myapp-secret
          backoffLimit: 1
```

**Pourquoi `concurrencyPolicy: Forbid` ?** Si l'exécution précédente n'est pas
terminée quand la minute suivante arrive, `Forbid` empêche de lancer une deuxième
instance. Ça évite que deux exécutions se marchent dessus (ex : deux envois de la même
newsletter).

---

## 4.7 Commandes kubectl essentielles

### Gestion des ressources

```bash
# Appliquer tous les manifests d'un coup (K8s détecte les changements)
kubectl apply -f infra/k8s/manifests/

# Appliquer un seul fichier
kubectl apply -f infra/k8s/manifests/deployment-php.yaml

# Voir les ressources
kubectl get pods -n myapp                 # Liste les pods
kubectl get deployments -n myapp          # Liste les deployments
kubectl get services -n myapp             # Liste les services
kubectl get ingress -n myapp              # Liste les ingress
kubectl get all -n myapp                  # Tout d'un coup

# Détails d'une ressource (utile pour le debug)
kubectl describe pod myapp-php-xxxxx -n myapp
kubectl describe deployment myapp-php -n myapp

# Supprimer
kubectl delete -f infra/k8s/manifests/        # Tout supprimer
kubectl delete pod myapp-php-xxxxx -n myapp    # Un seul pod
```

### Debug

```bash
# Logs
kubectl logs myapp-php-xxxxx -n myapp             # Logs du pod
kubectl logs myapp-php-xxxxx -n myapp -f          # Follow (temps réel)
kubectl logs myapp-php-xxxxx -n myapp --previous  # Logs du container précédent (utile après un crash)

# Shell dans un container (comme docker exec -it)
kubectl exec -it myapp-php-xxxxx -n myapp -- sh
kubectl exec -it myapp-php-xxxxx -n myapp -- php artisan tinker

# Port-forward : accéder à un service interne depuis ta machine
kubectl port-forward svc/myapp-nginx 8080:80 -n myapp    # http://localhost:8080
kubectl port-forward svc/postgres 5432:5432 -n myapp      # psql en local

# Copier des fichiers depuis/vers un pod
kubectl cp myapp/myapp-php-xxxxx:/var/www/html/storage/logs ./logs
```

**Pourquoi `port-forward` ?** Les Services ClusterIP ne sont pas accessibles depuis
l'extérieur du cluster. `port-forward` crée un tunnel temporaire entre ta machine et
le Service. Très utile pour accéder à PostgreSQL avec un client local, ou debugger
un service sans passer par l'Ingress.

### Scaling et rollout

```bash
# Scaler manuellement (augmenter/réduire le nombre de replicas)
kubectl scale deployment myapp-php --replicas=3 -n myapp

# Suivre un déploiement en cours
kubectl rollout status deployment myapp-php -n myapp

# Historique des déploiements
kubectl rollout history deployment myapp-php -n myapp

# Rollback à la version précédente (en cas de problème)
kubectl rollout undo deployment myapp-php -n myapp

# Forcer un redémarrage de tous les pods (sans changer l'image)
kubectl rollout restart deployment myapp-php -n myapp
```

**Pourquoi `rollout undo` est important ?** Imagine que tu déploies une nouvelle
version avec un bug. Au lieu de chercher le problème en urgence, tu fais
`rollout undo` : K8s recrée les pods avec l'image précédente. Ton app est de
nouveau fonctionnelle le temps que tu fixes le bug. C'est impossible avec Docker
Compose sans intervention manuelle.

---

## 4.8 Script de déploiement local

Ce script automatise toutes les étapes pour déployer l'app sur ton cluster k3d local.
Chaque étape est séquentielle, car elle dépend de la précédente.

```bash
#!/bin/bash
# infra/k8s/deploy-local.sh

set -e

NAMESPACE="myapp"
IMAGE_TAG="${1:-latest}"

# Étape 1 : Construire les images Docker
# On utilise le target "production" du Dockerfile car les manifests K8s
# n'utilisent pas de bind mounts (pas de code monté depuis l'hôte)
echo "=== Building images ==="
docker build -f infra/docker/php/Dockerfile --target production -t myapp-php:$IMAGE_TAG .
docker build -f infra/docker/nginx/Dockerfile --target production -t myapp-nginx:$IMAGE_TAG .

# Étape 2 : Importer les images dans k3d
# k3d n'a pas accès au Docker local de ta machine, il faut explicitement
# lui envoyer les images. Sans ça, les pods resteront en "ImagePullBackOff"
echo "=== Loading images into k3d ==="
k3d image import myapp-php:$IMAGE_TAG myapp-nginx:$IMAGE_TAG -c dev

# Étape 3 : Créer le namespace et la config
# On applique d'abord les ressources "fondation" dont les autres dépendent
echo "=== Applying manifests ==="
kubectl apply -f infra/k8s/manifests/namespace.yaml
kubectl apply -f infra/k8s/manifests/configmap.yaml
kubectl apply -f infra/k8s/manifests/secret.yaml
kubectl apply -f infra/k8s/manifests/pvc.yaml
kubectl apply -f infra/k8s/manifests/postgres.yaml
kubectl apply -f infra/k8s/manifests/redis.yaml

# Étape 4 : Attendre que la base de données soit prête
# Les migrations ont besoin de PostgreSQL. `kubectl wait` bloque jusqu'à
# ce que le pod soit ready (sa readinessProbe passe)
echo "=== Waiting for database ==="
kubectl wait --for=condition=ready pod -l app=postgres -n $NAMESPACE --timeout=120s

# Étape 5 : Déployer l'application
echo "=== Deploying application ==="
kubectl apply -f infra/k8s/manifests/deployment-php.yaml
kubectl apply -f infra/k8s/manifests/deployment-nginx.yaml
kubectl apply -f infra/k8s/manifests/service.yaml
kubectl apply -f infra/k8s/manifests/ingress.yaml

# Étape 6 : Exécuter les migrations
# Le Job crée un pod temporaire qui exécute les migrations puis se termine
echo "=== Running migrations ==="
kubectl apply -f infra/k8s/manifests/job-migrate.yaml
kubectl wait --for=condition=complete job/myapp-migrate -n $NAMESPACE --timeout=120s

# Étape 7 : Attendre que tous les pods applicatifs soient prêts
echo "=== Waiting for pods ==="
kubectl wait --for=condition=ready pod -l app=myapp -n $NAMESPACE --timeout=120s

echo "=== Deployment complete ==="
echo "Add '127.0.0.1 myapp.localhost' to /etc/hosts"
echo "Access: http://myapp.localhost"
```

---

## Checklist de fin de phase

- [ ] Cluster local fonctionnel (k3d, kind, ou minikube)
- [ ] kubectl configuré avec autocomplétion
- [ ] k9s installé
- [ ] Namespace créé
- [ ] ConfigMap et Secret configurés
- [ ] Deployments PHP et Nginx fonctionnels
- [ ] Services exposant les pods
- [ ] Ingress configuré
- [ ] PostgreSQL et Redis déployés
- [ ] Job de migration fonctionnel
- [ ] Application accessible en local
