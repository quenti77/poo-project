# Phase 3 : Premier déploiement VPS

Cette phase couvre le provisioning d'un VPS, sa sécurisation avec Ansible,
et le déploiement de l'application avec Docker Compose.

> **Contexte** : On travaille avec un **VPS Hostinger sous Ubuntu**. Les concepts
> présentes ici sont universels, mais les exemples concrets sont adapté à ce provider.

---

## 3.1 Choix et provisioning du VPS

### Pourquoi un VPS ?

Un VPS (Virtual Private Server) est une machine virtuelle qu'on loue chez un hébergeur.
Contrairement à un hébergement mutualise (ou on partage un serveur avec d'autres clients),
un VPS nous donne un accès **root** complet : on contrôle le système d'exploitation,
les logiciels installés, les ports ouverts, etc.

C'est le point de depart pour deployer une application de facon professionnelle,
car on maitrise toute la chaine :

- le système (Ubuntu)
- le runtime (Docker)
- le reverse proxy (Nginx/Caddy)
- la base de donnees
- les certificats SSL

### Hostinger VPS

Hostinger propose des VPS Ubuntu a des prix compétitifs. Voici ce qu'il faut savoir :

| Aspect       | Detail                                                              |
|--------------|---------------------------------------------------------------------|
| **OS**       | Ubuntu (22.04 ou 24.04 selon l'offre)                               |
| **Acces**    | SSH root (mot de passe initial + cle SSH)                           |
| **Panel**    | hPanel pour la gestion de base (reboot, reinstallation, etc.)       |
| **IP**       | IP fixe fournie a la creation                                       |
| **API**      | Pas d'API de provisioning (contrairement a Hetzner ou DigitalOcean) |
| **Firewall** | Pas de firewall cloud integre, on le gere avec UFW sur le serveur   |

> **Note** : Hostinger n'a pas d'API pour creer/detruire des serveurs par script.
> Le provisioning initial se fait via le hPanel (interface web). En revanche,
> toute la **configuration** du serveur sera automatisée avec Ansible.

### Dimensionnement minimal

Pour une application Laravel standard :

| Ressource      | Minimum   | Recommande |
|----------------|-----------|------------|
| CPU            | 1 vCPU    | 2 vCPU     |
| RAM            | 2 GB      | 4 GB       |
| Stockage       | 20 GB SSD | 40 GB SSD  |
| Bande passante | 1 TB      | Illimite   |

### Etapes de creation du VPS Hostinger

1. Se connecter au [hPanel Hostinger](https://hpanel.hostinger.com)
2. Commander un VPS (plan KVM)
3. Choisir Ubuntu 22.04 ou 24.04 comme OS
4. Noter l'IP du serveur et le mot de passe root initial
5. **Immédiatement** : ajouter sa cle SSH et désactiver l'accès par mot de passe (on verra comment dans la section
   securite).

### Premiere connexion

```bash
# Connexion initiale avec le mot de passe root fourni par Hostinger
ssh root@<IP_DU_VPS>

# Verifier la version d'Ubuntu
lsb_release -a

# Verifier les ressources
free -h    # RAM
df -h      # Espace disque
nproc      # Nombre de CPU
```

---

## 3.2 Ansible - Pourquoi et comment

### Le problème qu'Ansible résout

Imaginons qu'on configure notre VPS à la main : on installe Docker, on configure le firewall,
ont créé un utilisateur, on durcit SSH... Ça fonctionne, mais :

- **Ce n'est pas reproductible** : si on doit refaire la meme chose sur un autre serveur,
  on va oublier des étapes.
- **Ce n'est pas versionné** : pas de trace de ce qu'on a changé et quand
- **C'est risque** : une erreur de manipulation et on casse le serveur

Ansible résout ces problèmes en décrivant la configuration du serveur **dans des fichiers YAML**.
On décrit l'état souhaite ("je veux que Docker soit installe", "je veux que SSH refuse les mots de passe")
et Ansible s'occupe d'atteindre cet état.

### Concepts clés d'Ansible

| Concept        | Role                                | Analogie                                  |
|----------------|-------------------------------------|-------------------------------------------|
| **Inventaire** | Liste des serveurs a configurer     | Carnet d'adresses                         |
| **Playbook**   | Sequence de taches a executer       | Recette de cuisine                        |
| **Role**       | Groupe de taches reutilisable       | Chapitre d'un livre                       |
| **Task**       | Une action unitaire                 | Une etape de la recette                   |
| **Handler**    | Action declenchee par un changement | "Si la config SSH change, redemarrer SSH" |
| **Template**   | Fichier avec des variables (Jinja2) | Formulaire a trous                        |
| **Vault**      | Coffre-fort pour les secrets        | Coffre-fort                               |

> **Principe d'idempotence** : on peut exécuter un playbook Ansible autant de fois qu'on veut,
> le résultat sera toujours le meme. Si Docker est deja installe, Ansible ne le réinstallera pas.
> C'est un concept fondamental : ca permet de "rejouer" la config sans risque.

### Installation d'Ansible

Ansible s'installe sur **ta machine locale** (pas sur le VPS). C'est lui qui se connecte
en SSH au serveur pour appliquer la configuration.

```bash
# Linux (pip - recommande)
pip3 install ansible

# macOS
brew install ansible

# Verifier
ansible --version
```

### Structure recommandée

Chaque dossier a un role precis. Cette organisation est une convention Ansible :

```
infra/ansible/
├── ansible.cfg              # Configuration globale d'Ansible
├── inventory/
│   ├── production.yml       # Adresses des serveurs de production
│   └── staging.yml          # Adresses des serveurs de staging
├── playbooks/
│   ├── bootstrap.yml        # Configuration initiale (1ere fois)
│   ├── docker.yml           # Installation Docker
│   ├── deploy.yml           # Deploiement de l'application
│   └── site.yml             # Playbook principal (tout en un)
├── roles/
│   ├── common/              # Paquets de base, timezone, mises a jour
│   ├── docker/              # Installation et configuration Docker
│   ├── security/            # SSH, firewall, fail2ban
│   └── app/                 # Deploiement de l'application Laravel
├── group_vars/
│   ├── all.yml              # Variables partagees par tous les serveurs
│   └── production.yml       # Variables specifiques a la production
├── host_vars/
│   └── app-prod.yml         # Variables specifiques a un serveur
└── files/
    └── ...                  # Fichiers statiques a copier
```

### Configuration Ansible

Le fichier `ansible.cfg` dit a Ansible comment se comporter par défaut.

```ini
# infra/ansible/ansible.cfg

[defaults]
# Ou trouver la liste des serveurs
inventory = inventory/production.yml
# Ou trouver les roles
roles_path = roles
# Ne pas demander de confirmation pour les nouvelles cles SSH
host_key_checking = False
# Ne pas créer de fichiers .retry en cas d'echec
retry_files_enabled = False
# Afficher les résultats en YAML (plus lisible que le JSON par défaut)
stdout_callback = yaml

[ssh_connection]
# Pipelining = envoie plusieurs commandes dans une seule connexion SSH
# Ca accélère considérablement l'exécution
pipelining = True
control_path = /tmp/ansible-%%r@%%h:%%p
```

### Inventaire

L'inventaire dit a Ansible **quels serveurs** configurer et **comment** s'y connecter.

```yaml
# infra/ansible/inventory/production.yml

all:
  children:
    # On regroupe les serveurs par role (ici, un seul groupe "webservers")
    webservers:
      hosts:
        app-prod:
          # L'IP de ton VPS Hostinger
          ansible_host: <IP_DU_VPS_HOSTINGER>
          # L'utilisateur SSH (root au debut, puis "deploy" apres le bootstrap)
          ansible_user: root
          ansible_python_interpreter: /usr/bin/python3

  vars:
    # Cle SSH a utiliser pour la connexion
    ansible_ssh_private_key_file: ~/.ssh/hostinger
    app_domain: myapp.com
    app_env: production
```

> **Pourquoi `ansible_ssh_private_key_file: ~/.ssh/hostinger`** ?
> C'est la cle SSH privee qui correspond a la cle publique qu'on aura deposee
> sur le VPS. Chez toi, tu as deja une cle `~/.ssh/hostinger` - c'est celle-la
> qu'il faut utiliser.

---

## 3.3 Role : Common (configuration de base)

Ce role installe les paquets essentiels et configure les bases du serveur.
C'est le minimum qu'on veut sur n'importe quel serveur Ubuntu.

**Pourquoi ces paquets ?**

- `curl`, `wget` : télécharger des fichiers/scripts
- `git` : cloner des repos (utile pour les deploys)
- `htop`, `ncdu` : diagnostic (CPU/RAM et espace disque)
- `apt-transport-https`, `ca-certificates`, `gnupg` : necessaires pour ajouter
  des depots APT securises (comme celui de Docker)

```yaml
# infra/ansible/roles/common/tasks/main.yml

---
- name: Update apt cache
  apt:
    update_cache: yes
    # Ne mettre à jour le cache que s'il a plus d'une heure
    cache_valid_time: 3600

- name: Install essential packages
  apt:
    name:
      - curl
      - wget
      - git
      - vim
      - htop
      - ncdu
      - unzip
      - apt-transport-https
      - ca-certificates
      - gnupg
      - lsb-release
    state: present

- name: Set timezone
  timezone:
    name: "{{ timezone | default('UTC') }}"

- name: Configure automatic security updates
  apt:
    name: unattended-upgrades
    state: present

- name: Enable automatic security updates
  copy:
    dest: /etc/apt/apt.conf.d/20auto-upgrades
    content: |
      APT::Periodic::Update-Package-Lists "1";
      APT::Periodic::Unattended-Upgrade "1";
      APT::Periodic::AutocleanInterval "7";
    mode: '0644'
```

> **`unattended-upgrades`** est crucial en production : il applique automatiquement
> les mises à jour de securite sans intervention manuelle. Sans ça, il suffit
> d'une faille non patchée pour se faire compromettre.

---

## 3.4 Role : Security (sécurisation SSH et firewall)

C'est le role le plus important. Un VPS expose sur Internet et scanne en permanence
par des bots qui testent des mots de passe SSH. Il faut se protéger :

1. **Créer un utilisateur non-root** pour les operations courantes
2. **Désactiver l'accès SSH par mot de passe** (cle SSH uniquement)
3. **Désactiver le login root** par SSH
4. **Configurer un firewall** (UFW) pour n'ouvrir que les ports necessaires
5. **Installer fail2ban** pour bannir les IPs qui font du brute-force

### Tasks

```yaml
# infra/ansible/roles/security/tasks/main.yml

---
# === UTILISATEUR DE DÉPLOIEMENT ===
# On crée un utilisateur "deploy" qui servira pour toutes les operations.
# Il a les droits sudo mais on ne se connecte plus en root.

- name: Create deploy user
  user:
    name: "{{ deploy_user }}"
    groups: sudo
    shell: /bin/bash
    create_home: yes
    state: present

# On copie notre cle SSH publique pour cet utilisateur,
# ce qui permet de se connecter en SSH sans mot de passe.
- name: Add SSH key for deploy user
  authorized_key:
    user: "{{ deploy_user }}"
    key: "{{ lookup('file', deploy_user_ssh_key) }}"
    state: present

# On donne les droits sudo sans mot de passe au deploy user.
# "visudo -cf %s" valide la syntaxe du fichier sudoers
# (une erreur ici pourrait nous bloquer hors du serveur).
- name: Configure sudo without password for deploy user
  lineinfile:
    path: /etc/sudoers.d/{{ deploy_user }}
    line: "{{ deploy_user }} ALL=(ALL) NOPASSWD:ALL"
    create: yes
    mode: '0440'
    validate: 'visudo -cf %s'

# === SÉCURISATION SSH ===
# Chaque ligne modifie un paramètre de /etc/ssh/sshd_config.
# L'objectif : n'autoriser que les connexions par cle SSH.

- name: Secure SSH configuration
  lineinfile:
    path: /etc/ssh/sshd_config
    regexp: "{{ item.regexp }}"
    line: "{{ item.line }}"
    state: present
  loop:
    # Interdit la connexion en root par SSH
    - { regexp: '^#?PermitRootLogin', line: 'PermitRootLogin no' }
    # Interdit l'authentification par mot de passe
    - { regexp: '^#?PasswordAuthentication', line: 'PasswordAuthentication no' }
    # Active l'authentification par cle publique
    - { regexp: '^#?PubkeyAuthentication', line: 'PubkeyAuthentication yes' }
    # Désactivé l'authentification challenge-response
    - { regexp: '^#?ChallengeResponseAuthentication', line: 'ChallengeResponseAuthentication no' }
    # PAM reste actif (necessaire pour certains services)
    - { regexp: '^#?UsePAM', line: 'UsePAM yes' }
    # Désactivé le forwarding X11 (interface graphique a distance - inutile)
    - { regexp: '^#?X11Forwarding', line: 'X11Forwarding no' }
    # Maximum 3 tentatives de connexion avant déconnexion
    - { regexp: '^#?MaxAuthTries', line: 'MaxAuthTries 3' }
  notify: Restart SSH

# === FIREWALL (UFW) ===
# UFW (Uncomplicated Firewall) est un frontend simplifie pour iptables.
# Sur Hostinger, il n'y a pas de firewall cloud, donc UFW est notre
# seule couche de protection réseau.

- name: Install and configure UFW
  apt:
    name: ufw
    state: present

# Politique par défaut : tout bloquer en entree, tout autoriser en sortie.
# C'est le principe du "deny by default" : on n'ouvre que ce qu'on a besoin.
- name: Set UFW default policies
  ufw:
    direction: "{{ item.direction }}"
    policy: "{{ item.policy }}"
  loop:
    - { direction: incoming, policy: deny }
    - { direction: outgoing, policy: allow }

# On ouvre uniquement les ports necessaires :
# - 22 : SSH (pour se connecter)
# - 80 : HTTP (pour le challenge Let's Encrypt et la redirection vers HTTPS)
# - 443 : HTTPS (pour le trafic web)
- name: Allow SSH
  ufw:
    rule: allow
    port: "{{ ssh_port | default('22') }}"
    proto: tcp

- name: Allow HTTP
  ufw:
    rule: allow
    port: '80'
    proto: tcp

- name: Allow HTTPS
  ufw:
    rule: allow
    port: '443'
    proto: tcp

- name: Enable UFW
  ufw:
    state: enabled

# === FAIL2BAN ===
# fail2ban surveille les logs (notamment /var/log/auth.log) et bannit
# les adresses IP qui font trop de tentatives de connexion échouées.
# C'est une protection contre le brute-force SSH.

- name: Install fail2ban
  apt:
    name: fail2ban
    state: present

- name: Configure fail2ban for SSH
  copy:
    dest: /etc/fail2ban/jail.local
    content: |
      [DEFAULT]
      # Durée du ban par défaut
      bantime = 1h
      # Fenêtre de temps pour compter les tentatives
      findtime = 10m
      # Nombre de tentatives avant ban
      maxretry = 5

      [sshd]
      enabled = true
      port = {{ ssh_port | default('22') }}
      filter = sshd
      logpath = /var/log/auth.log
      # Plus strict pour SSH : 3 tentatives, ban de 24h
      maxretry = 3
      bantime = 24h
    mode: '0644'
  notify: Restart fail2ban
```

### Handlers

Les handlers sont des actions déclenchées **uniquement quand une task signale un changement**
via `notify`. Par exemple, si la config SSH n'a pas change, le handler `Restart SSH`
ne sera pas execute. C'est un mecanisme d'optimisation.

```yaml
# infra/ansible/roles/security/handlers/main.yml

---
- name: Restart SSH
  service:
    name: sshd
    state: restarted

- name: Restart fail2ban
  service:
    name: fail2ban
    state: restarted
```

### Valeurs par defaut

Les `defaults` sont les valeurs utilisées si on ne les surcharge pas
dans l'inventaire ou les `group_vars`. C'est le mécanisme de configuration
d'Ansible : on définit des valeurs par défaut sensées, qu'on peut
personnaliser par serveur ou par groupe.

```yaml
# infra/ansible/roles/security/defaults/main.yml

---
deploy_user: deploy
deploy_user_ssh_key: ~/.ssh/hostinger.pub
ssh_port: 22
```

---

## 3.5 Role : Docker

Ce role installe Docker CE (Community Edition) sur le VPS.

**Pourquoi Docker sur le VPS ?** Plutôt que d'installer PHP, Nginx, PostgreSQL, Redis
directement sur le serveur (ce qui créé un melange difficile à maintenir), on fait tourner
chaque composant dans un conteneur Docker isolé. Ça rend le déploiement reproductible
et le serveur facilement nettoyable.

**Pourquoi supprimer les anciennes versions ?** Ubuntu peut avoir des paquets Docker
pre-installes (versions obsoletes). On les supprime pour installer la version officielle
depuis le depot Docker.

```yaml
# infra/ansible/roles/docker/tasks/main.yml

---
- name: Remove old Docker versions
  apt:
    name:
      - docker
      - docker-engine
      - docker.io
      - containerd
      - runc
    state: absent

# On ajoute la cle GPG du depot Docker officiel.
# Sans cette cle, APT refuserait d'installer les paquets
# (protection contre les paquets non signes).
- name: Add Docker GPG key
  apt_key:
    url: https://download.docker.com/linux/ubuntu/gpg
    state: present

# On ajoute le depot APT officiel de Docker.
# "ansible_distribution_release" est automatiquement renseigne par Ansible
# (ex: "jammy" pour Ubuntu 22.04, "noble" pour 24.04).
- name: Add Docker repository
  apt_repository:
    repo: "deb [arch=amd64] https://download.docker.com/linux/ubuntu {{ ansible_distribution_release }} stable"
    state: present

- name: Install Docker
  apt:
    name:
      - docker-ce           # Le moteur Docker
      - docker-ce-cli       # Le CLI docker
      - containerd.io       # Le runtime de conteneurs
      - docker-buildx-plugin   # Builder améliore (multi-architecture)
      - docker-compose-plugin  # docker compose (v2, intègre au CLI)
    state: present
    update_cache: yes

- name: Start and enable Docker
  service:
    name: docker
    state: started
    # "enabled" = Docker démarre automatiquement au boot du serveur
    enabled: yes

# On ajoute notre utilisateur "deploy" au groupe docker.
# Sans cela, il faudrait utiliser "sudo" pour chaque commande docker.
- name: Add deploy user to docker group
  user:
    name: "{{ deploy_user }}"
    groups: docker
    append: yes

# Configuration du daemon Docker :
# - log-driver json-file avec rotation (max 10 Mo, max 3 fichiers)
#   Sans ça, les logs Docker grossissent indéfiniment et remplissent le disque.
# - overlay2 : driver de stockage le plus performant sur Linux.
- name: Configure Docker daemon
  copy:
    dest: /etc/docker/daemon.json
    content: |
      {
        "log-driver": "json-file",
        "log-opts": {
          "max-size": "10m",
          "max-file": "3"
        },
        "storage-driver": "overlay2"
      }
    mode: '0644'
  notify: Restart Docker

# On va créer un réseau Docker dédié à l'application.
# Tous les conteneurs sur ce réseau peuvent communiquer entre eux
# par leur nom (resolution DNS interne Docker).
- name: Create Docker network for app
  docker_network:
    name: "{{ app_network | default('app-network') }}"
    state: present
```

```yaml
# infra/ansible/roles/docker/handlers/main.yml

---
- name: Restart Docker
  service:
    name: docker
    state: restarted
```

---

## 3.6 Role : App (déploiement)

Ce role déploie l'application Laravel sur le VPS via Docker Compose.

**Le flux** :

1. Créer les dossiers necessaires sur le serveur
2. Copier le `docker-compose.yml` (généré depuis un template Jinja2)
3. Copier le fichier `.env` (généré depuis un template avec les secrets)
4. Tirer les images Docker et démarrer les conteneurs
5. Lancer les migrations Laravel
6. Mettre en cache la configuration Laravel

### Tasks

```yaml
# infra/ansible/roles/app/tasks/main.yml

---
- name: Create app directory
  file:
    path: "{{ app_path }}"
    state: directory
    owner: "{{ deploy_user }}"
    group: "{{ deploy_user }}"
    mode: '0755'

# Laravel a besoin de ces dossiers pour le cache, les sessions
# et les logs. On les cree à l'avance avec les bons droits.
- name: Create required directories
  file:
    path: "{{ app_path }}/{{ item }}"
    state: directory
    owner: "{{ deploy_user }}"
    group: "{{ deploy_user }}"
    mode: '0755'
  loop:
    - storage
    - storage/logs
    - storage/framework/cache
    - storage/framework/sessions
    - storage/framework/views

# "template" copie un fichier Jinja2 (.j2) en remplaçant les {{ variables }}
# par leurs valeurs. C'est comme ça qu'on injecte les paramètres
# (domaine, version, etc.) dans le docker-compose.
- name: Copy docker-compose file
  template:
    src: docker-compose.yml.j2
    dest: "{{ app_path }}/docker-compose.yml"
    owner: "{{ deploy_user }}"
    group: "{{ deploy_user }}"
    mode: '0644'

# Le .env contient les secrets (cles, mots de passe).
# Mode 0600 = seul le proprietaire peut le lire.
- name: Copy environment file
  template:
    src: env.j2
    dest: "{{ app_path }}/.env"
    owner: "{{ deploy_user }}"
    group: "{{ deploy_user }}"
    mode: '0600'

- name: Login to container registry
  docker_login:
    registry: "{{ docker_registry }}"
    username: "{{ docker_registry_user }}"
    password: "{{ docker_registry_password }}"
  when: docker_registry is defined

- name: Pull latest images
  community.docker.docker_compose_v2:
    project_src: "{{ app_path }}"
    pull: always
  become: yes
  become_user: "{{ deploy_user }}"

- name: Start application
  community.docker.docker_compose_v2:
    project_src: "{{ app_path }}"
    state: present
  become: yes
  become_user: "{{ deploy_user }}"

- name: Run migrations
  community.docker.docker_container_exec:
    container: "{{ app_container_name }}"
    command: php artisan migrate --force
  become: yes
  become_user: "{{ deploy_user }}"

# On met en cache la config, les routes et les vues pour
# des performances optimales en production.
# - config:cache = compile toute la config en un seul fichier
# - route:cache = serialise les routes (chargement plus rapide)
# - view:cache = pre-compile les templates Blade
- name: Clear and cache config
  community.docker.docker_container_exec:
    container: "{{ app_container_name }}"
    command: "{{ item }}"
  loop:
    - php artisan config:cache
    - php artisan route:cache
    - php artisan view:cache
  become: yes
  become_user: "{{ deploy_user }}"
```

```yaml
# infra/ansible/roles/app/defaults/main.yml

---
app_path: /opt/app
app_container_name: app-php
app_network: app-network
deploy_user: deploy
```

### Template docker-compose

Ce fichier est un template **Jinja2** (extension `.j2`). Les `{{ variable }}`
sont remplacées par Ansible au moment de la copie. C'est ce qui permet d'avoir
un seul template pour plusieurs environnements (staging, production).

```yaml
# infra/ansible/roles/app/templates/docker-compose.yml.j2

services:
  nginx:
    image: { { docker_registry } }/{{ app_name }}-nginx:{{ app_version | default('latest') }}
    ports:
      - "80:80"
    depends_on:
      - php
    networks:
      - { { app_network } }
    restart: unless-stopped

  php:
    image: { { docker_registry } }/{{ app_name }}-php:{{ app_version | default('latest') }}
    container_name: { { app_container_name } }
    env_file:
      - .env
    volumes:
      # On monte le storage en volume pour persister les fichiers uploades
      # et les logs entre les redemarrages du conteneur
      - ./storage:/var/www/html/storage
    depends_on:
      db:
        # "service_healthy" attend que le healthcheck de PostgreSQL passe
        # avant de demarrer PHP. Ca evite les erreurs "connection refused"
        # au demarrage.
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - { { app_network } }
    restart: unless-stopped

  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: { { db_database } }
      POSTGRES_USER: { { db_username } }
      POSTGRES_PASSWORD: { { db_password } }
    volumes:
      # Volume nomme pour persister les donnees PostgreSQL
      # meme si le conteneur est supprime et recree
      - db-data:/var/lib/postgresql/data
    healthcheck:
      test: [ "CMD-SHELL", "pg_isready -U {{ db_username }}" ]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - { { app_network } }
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    # --appendonly yes = persistence AOF (chaque ecriture est enregistree)
    # --requirepass = protege Redis par un mot de passe
    command: redis-server --appendonly yes --requirepass {{ redis_password }}
    volumes:
      - redis-data:/data
    networks:
      - { { app_network } }
    restart: unless-stopped

# Le reseau "external: true" signifie qu'il a ete cree en dehors
# de ce docker-compose (par le role Docker). Ca evite que chaque
# "docker compose up" essaie de le recreer.
networks:
  { { app_network } }:
    external: true

volumes:
  db-data:
  redis-data:
```

### Template .env

```bash
# infra/ansible/roles/app/templates/env.j2

APP_NAME="{{ app_name }}"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://{{ app_domain }}
APP_KEY={{ app_key }}

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE={{ db_database }}
DB_USERNAME={{ db_username }}
DB_PASSWORD={{ db_password }}

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD={{ redis_password }}

MAIL_MAILER=smtp
MAIL_HOST={{ mail_host | default('localhost') }}
MAIL_PORT={{ mail_port | default('587') }}
```

> **Pourquoi `DB_HOST=db` et `REDIS_HOST=redis` ?** Dans Docker Compose,
> chaque service est accessible par son nom depuis les autres conteneurs
> du meme reseau. Le service `db` est donc joignable a l'adresse `db:5432`.

---

## 3.7 Playbooks

Les playbooks orchestrent les roles. On en a trois :

### Bootstrap (premiere execution)

Le bootstrap s'exécuté **une seule fois**, en root, sur un serveur vierge.
Il installe les bases, sécurise le serveur et installe Docker.

Apres cette étape, on ne se connecte plus en root mais avec l'utilisateur `deploy`.

```yaml
# infra/ansible/playbooks/bootstrap.yml

---
- name: Bootstrap server
  hosts: webservers
  become: yes

  roles:
    - common
    - security
    - docker

  post_tasks:
    - name: Reboot server
      reboot:
        msg: "Reboot after bootstrap"
        reboot_timeout: 300
      when: reboot_required | default(false)
```

### Déploiement

Le playbook de déploiement s'exécute à chaque nouvelle version.
`vars_prompt` demande interactivement quelle version deployer.

```yaml
# infra/ansible/playbooks/deploy.yml

---
- name: Deploy application
  hosts: webservers
  become: yes

  vars_prompt:
    - name: app_version
      prompt: "Version to deploy (tag/sha)"
      default: "latest"
      private: no

  roles:
    - app

  post_tasks:
    # On vérifie que l'application répond bien apres le déploiement.
    # "retries: 5" avec "delay: 10" = on attend jusqu'à 50 secondes
    # que le health check passe (le temps que les conteneurs demarrent).
    - name: Verify application is running
      uri:
        url: "https://{{ app_domain }}/health"
        status_code: 200
      register: health_check
      retries: 5
      delay: 10
      until: health_check.status == 200

    - name: Display deployment result
      debug:
        msg: "Application deployed successfully at https://{{ app_domain }}"
```

### Site (tout-en-un)

Ce playbook execute tout : bootstrap + deploiement. Utile pour provisionner
un serveur de zero en une seule commande.

```yaml
# infra/ansible/playbooks/site.yml

---
- name: Full server setup and deployment
  hosts: webservers
  become: yes

  roles:
    - common
    - security
    - docker
    - app
```

---

## 3.8 Variables et secrets

### Variables globales

Ces variables sont partagées par tous les serveurs de l'inventaire.

```yaml
# infra/ansible/group_vars/all.yml

---
# Application
app_name: myapp
timezone: UTC

# Deploy user
deploy_user: deploy
deploy_user_ssh_key: ~/.ssh/hostinger.pub

# Docker
app_network: app-network
docker_registry: ghcr.io/username
```

### Variables production (chiffrées avec Ansible Vault)

Les mots de passe et clés ne doivent **jamais** être en clair dans Git.
On utilise Ansible Vault pour les chiffrer.

Les variables préfixées par `vault_` viennent du fichier vault chiffre.

```yaml
# infra/ansible/group_vars/production.yml

---
app_domain: myapp.com
app_env: production

# Secrets (chiffres avec ansible-vault)
app_key: "{{ vault_app_key }}"
db_database: app
db_username: app
db_password: "{{ vault_db_password }}"
redis_password: "{{ vault_redis_password }}"
```

### Utilisation d'Ansible Vault

Ansible Vault chiffre un fichier YAML avec un mot de passe.
Le fichier chiffre peut aller dans Git sans risque : il est illisible sans le mot de passe.

```bash
# Creer un fichier vault (ouvre un editeur)
ansible-vault create group_vars/vault.yml

# Contenu du vault (en clair dans l'editeur, chiffre sur disque) :
# vault_app_key: "base64:xxxxxxxxxxxxxxxx"
# vault_db_password: "supersecretpassword"
# vault_redis_password: "anothersecret"

# Editer le vault plus tard
ansible-vault edit group_vars/vault.yml

# Executer un playbook qui utilise des variables vault :
# --ask-vault-pass = demande le mot de passe du vault interactivement
ansible-playbook playbooks/deploy.yml --ask-vault-pass

# Ou avec un fichier contenant le mot de passe (utile pour le CI/CD)
ansible-playbook playbooks/deploy.yml --vault-password-file ~/.vault_pass
```

> **Bonne pratique** : le fichier `~/.vault_pass` ne doit **jamais** être dans Git.
> Ajoutez-le a votre `.gitignore` global.

---

## 3.9 Execution

### Premier déploiement

```bash
cd infra/ansible

# 1. Bootstrap du serveur (en root, car l'utilisateur deploy n'existe pas encore)
ansible-playbook playbooks/bootstrap.yml -u root --ask-vault-pass

# 2. IMPORTANT : mettre a jour l'inventaire pour utiliser le nouvel utilisateur
# Dans inventory/production.yml, changer : ansible_user: deploy
# A partir de maintenant, root est inaccessible par SSH.

# 3. Deployer l'application
ansible-playbook playbooks/deploy.yml --ask-vault-pass
```

### Déploiements suivants

```bash
# Deployer une version spécifique
ansible-playbook playbooks/deploy.yml -e "app_version=v1.2.3" --ask-vault-pass

# Deployer seulement (sans provisioner)
ansible-playbook playbooks/deploy.yml --tags deploy --ask-vault-pass
```

### Commandes utiles

```bash
# Tester la connexion a tous les serveurs
ansible webservers -m ping

# Lister les hôtes de l'inventaire
ansible-inventory --list

# Dry-run : voir ce qui SERAIT change sans rien appliquer
# --check = mode simulation, --diff = affiche les differences
ansible-playbook playbooks/deploy.yml --check --diff

# Exécuter une commande ponctuelle sur tous les serveurs
ansible webservers -a "docker ps"
ansible webservers -a "df -h"
```

---

## 3.10 Reverse proxy avec Caddy (alternative simple)

**Pourquoi un reverse proxy ?** Le conteneur PHP-FPM ne gère pas HTTPS ni les fichiers
statiques. Il faut un serveur web devant lui. Deux choix :

|                 | Nginx                                  | Caddy                     |
|-----------------|----------------------------------------|---------------------------|
| **HTTPS**       | Configuration manuelle + Let's Encrypt | Automatique (zero config) |
| **Config**      | Verbose mais flexible                  | Minimale                  |
| **Performance** | Reference du marche                    | Tres bon                  |
| **Complexite**  | Moyenne                                | Faible                    |

Pour un premier déploiement, **Caddy est recommande** car il gère les certificats
Let's Encrypt automatiquement. Il suffit de pointer un domaine vers l'IP du VPS
et Caddy s'occupe du reste.

```yaml
# Ajouter Caddy au docker-compose
services:
  caddy:
    image: caddy:2-alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile
      # caddy-data stocke les certificats Let's Encrypt
      - caddy-data:/data
      - caddy-config:/config
    depends_on:
      - php
    networks:
      - app-network
    restart: unless-stopped

volumes:
  caddy-data:
  caddy-config:
```

```
# Caddyfile
# C'est tout ce qu'il faut ! Caddy detecte le domaine,
# obtient un certificat Let's Encrypt, et configure HTTPS.
{$APP_DOMAIN} {
    root * /srv/public

    encode gzip

    php_fastcgi php:9000
    file_server

    header {
        X-Frame-Options "SAMEORIGIN"
        X-Content-Type-Options "nosniff"
        X-XSS-Protection "1; mode=block"
    }

    log {
        output stdout
    }
}
```

---

## Checklist de fin de phase

- [ ] VPS Hostinger provisionne et accessible en SSH
- [ ] Ansible installe et configure sur la machine locale
- [ ] Role security : SSH sécurisé (cle uniquement), firewall UFW actif, fail2ban installe
- [ ] Role docker : Docker installe et configure avec rotation des logs
- [ ] Role app : déploiement automatise via Ansible
- [ ] Secrets gérés avec Ansible Vault (jamais en clair dans Git)
- [ ] Application accessible en HTTPS (via Caddy ou Nginx + Let's Encrypt)
- [ ] Health check fonctionnel (`/health` repond 200)
- [ ] Procédure de déploiement documentée et reproductible
