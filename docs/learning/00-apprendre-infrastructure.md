# Phase 0 : Apprendre l'infrastructure - Méthode et outils

Tu sais installer un serveur à la main, configurer des services, SSH dessus et tout faire
en ligne de commande. Le problème : dès qu'il faut automatiser (Dockerfile, Ansible, Terraform...),
tu te retrouves devant une page blanche.

Ce guide explique **comment apprendre** ces outils, pas les outils eux-mêmes.

---

## 0.1 Le déclic mental : de "faire" à "décrire"

### Pourquoi c'est difficile ?

L'administration manuelle est **impérative** : tu tapes des commandes une par une, tu vois le résultat, tu corriges.
L'automatisation est **déclarative** : tu décris un état final et l'outil se débrouille pour y arriver.

```
# Manuel (impératif) : je fais les choses étape par étape
apt update
apt install nginx
vim /etc/nginx/sites-available/app.conf
systemctl enable nginx
systemctl start nginx

# Ansible (déclaratif) : je décris ce que je veux
- name: Nginx is installed and running
  apt:
    name: nginx
    state: present
- name: Nginx is enabled
  systemd:
    name: nginx
    enabled: yes
    state: started
```

Le réflexe à acquérir : **au lieu de "qu'est-ce que je tape ?", se demander "quel état je veux ?"**

### La boucle d'apprentissage

```
┌──────────────────────────────────────────────────────────┐
│                                                          │
│   1. Faire à la main    ──▶   2. Observer ce qu'on a     │
│      (tu sais déjà)              fait (noter les étapes) │
│                                                          │
│          ▲                              │                │
│          │                              ▼                │
│                                                          │
│   4. Comparer le        ◀──   3. Traduire en code        │
│      résultat                    (Dockerfile, playbook)  │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

Concrètement :

1. Installe le service manuellement sur une VM jetable
2. Note chaque commande que tu as tapée (historique bash : `history`)
3. Traduis ces commandes en fichier d'automatisation
4. Détruis la VM, relance l'automatisation, vérifie que le résultat est identique

---

## 0.2 Où trouver les informations

### La documentation officielle d'abord

La doc officielle est **toujours** le premier endroit où chercher. Pas les tutos Medium, pas les vidéos YouTube de 2019.

| Outil              | Documentation officielle                       | Ce qu'il faut lire en premier                                                     |
|--------------------|------------------------------------------------|-----------------------------------------------------------------------------------|
| **Docker**         | https://docs.docker.com                        | [Dockerfile reference](https://docs.docker.com/reference/dockerfile/)             |
| **Docker Compose** | https://docs.docker.com/compose/               | [Compose file reference](https://docs.docker.com/compose/compose-file/)           |
| **Ansible**        | https://docs.ansible.com                       | [Getting started guide](https://docs.ansible.com/ansible/latest/getting_started/) |
| **Terraform**      | https://developer.hashicorp.com/terraform/docs | [Get Started tutorials](https://developer.hashicorp.com/terraform/tutorials)      |
| **Nginx**          | https://nginx.org/en/docs/                     | [Beginner's guide](https://nginx.org/en/docs/beginners_guide.html)                |
| **Kubernetes**     | https://kubernetes.io/docs/                    | [Tutorials](https://kubernetes.io/docs/tutorials/)                                |

### Comment lire une doc technique

Ne pas essayer de tout lire. Procéder ainsi :

1. **Getting Started / Quick Start** : faire le tuto officiel de bout en bout
2. **Concepts** : comprendre le vocabulaire (image, container, playbook, module...)
3. **Reference** : garder sous la main pour chercher les options d'une directive
4. **Examples** : s'en inspirer, ne pas copier-coller aveuglément

### Chercher des réponses efficacement

```
# Mauvaise recherche Google
"ansible tutorial"
"dockerfile example"

# Bonne recherche
"ansible apt module install specific version"
"dockerfile COPY vs ADD difference"
"docker compose healthcheck postgres example"
"ansible handlers notify explained"
```

Règles :

- Chercher **un problème précis**, pas un concept général
- Ajouter le nom du **module/directive** spécifique
- Privilégier les résultats de **docs officielles, Stack Overflow, GitHub issues**
- Vérifier la **date** : un article de 2018 sur Docker est probablement obsolète

### Lire les projets open source

Les vrais projets sont la meilleure source d'apprentissage :

- Chercher sur GitHub : `filename:Dockerfile language:dockerfile php laravel`
- Chercher sur GitHub : `filename:playbook.yml ansible nginx`
- Regarder comment les images officielles sont construites : https://github.com/docker-library

---

## 0.3 Comment tester quand on apprend

### Le problème

Tu ne vas pas tester Ansible sur ton serveur de production. Tu ne vas pas non plus acheter
un VPS pour chaque expérimentation. Il faut un **environnement jetable**.

### Docker : tester directement

Docker est le plus simple à tester, car il **est** l'environnement jetable.

```bash
# Construire et voir ce qui se passe
docker build -t test .

# Si ça plante, lire l'erreur, corriger le Dockerfile, relancer
# Chaque build est indépendant

# Lancer un container pour explorer
docker run -it --rm test /bin/sh

# Vérifier ce qui est installé, les fichiers copiés, les permissions
ls -la /var/www/html
php -v
nginx -t
```

**Technique clé : le Dockerfile de debug**

Quand un build échoue, commenter les lignes après l'erreur et explorer :

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache postgresql-dev
RUN docker-php-ext-install pdo_pgsql

# Tout ce qui est en dessous est commenté pour debug
# COPY . /app
# RUN composer install
```

```bash
docker build -t debug .
docker run -it --rm debug /bin/sh
# Explorer le container, tester les commandes manuellement
# Une fois que ça marche, remettre les lignes dans le Dockerfile
```

### Ansible : environnement de test local

Ansible a besoin de machines cibles. Plusieurs options :

#### Option 1 : Containers Docker comme cibles (rapide)

```yaml
# docker-compose.test-ansible.yml
services:
  target:
    image: ubuntu:22.04
    command: /usr/sbin/sshd -D
    # Ou utiliser l'image willhallonline/ansible-target
    ports:
      - "2222:22"
```

```bash
# Tester un playbook sur le container
ansible-playbook -i "localhost:2222," playbook.yml
```

#### Option 2 : Vagrant + VirtualBox (le plus réaliste)

```ruby
# Vagrantfile
Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/jammy64"
  config.vm.network "private_network", ip: "192.168.56.10"

  config.vm.provider "virtualbox" do |vb|
    vb.memory = "1024"
    vb.cpus = 1
  end

  # Provisionner avec Ansible automatiquement
  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "playbook.yml"
  end
end
```

```bash
# Créer la VM
vagrant up

# Relancer le provisioning sans recréer la VM
vagrant provision

# Se connecter en SSH pour vérifier
vagrant ssh

# Tout détruire et recommencer
vagrant destroy -f && vagrant up
```

#### Option 3 : Molecule (tests automatisés pour rôles Ansible)

```bash
pip install molecule molecule-docker

# Créer un rôle avec tests intégrés
molecule init role my_role

# Lancer les tests (crée un container, applique le rôle, vérifie)
cd my_role
molecule test

# Garder le container pour explorer
molecule converge
molecule login
```

#### La méthode pas-à-pas pour Ansible

```bash
# 1. Créer un inventaire minimal
echo "192.168.56.10 ansible_user=vagrant ansible_ssh_private_key_file=.vagrant/machines/default/virtualbox/private_key" > inventory

# 2. Tester la connexion
ansible all -i inventory -m ping

# 3. Tester UNE tâche en ad-hoc
ansible all -i inventory -m apt -a "name=nginx state=present" --become

# 4. Si ça marche, mettre dans un playbook
# 5. Ajouter les tâches une par une, en testant à chaque fois

# Option : mode check (dry-run) pour voir ce qui changerait
ansible-playbook -i inventory playbook.yml --check --diff
```

### Terraform : tester sans cloud

```bash
# Terraform a un mode plan qui ne fait rien pour de vrai
terraform plan

# Pour tester sans compte cloud, utiliser le provider Docker
# Ça crée de vrais containers localement via Terraform
```

```hcl
# main.tf - Provider Docker (pas besoin de compte cloud)
terraform {
  required_providers {
    docker = {
      source = "kreuzwerker/docker"
    }
  }
}

provider "docker" {}

resource "docker_image" "nginx" {
  name = "nginx:latest"
}

resource "docker_container" "web" {
  image = docker_image.nginx.image_id
  name  = "test-nginx"
  ports {
    internal = 80
    external = 8080
  }
}
```

```bash
terraform init
terraform plan    # Voir ce qui va être créé (sans rien faire)
terraform apply   # Créer les ressources
terraform destroy # Tout supprimer
```

---

## 0.4 Méthode d'apprentissage concrète

### Exercice fondateur : le serveur web

Objectif : servir une page HTML avec Nginx. D'abord à la main, puis automatisé.

#### Étape 1 : à la main (ce que tu sais faire)

```bash
# Sur une VM fraîche
sudo apt update && sudo apt install -y nginx
echo "<h1>Hello</h1>" | sudo tee /var/www/html/index.html
sudo systemctl start nginx
curl localhost  # Ça marche
```

#### Étape 2 : traduire en Dockerfile

```dockerfile
FROM nginx:alpine
COPY index.html /usr/share/nginx/html/index.html
```

```bash
echo "<h1>Hello</h1>" > index.html
docker build -t my-nginx .
docker run -p 8080:80 my-nginx
curl localhost:8080  # Ça marche pareil
```

#### Étape 3 : traduire en Ansible

```yaml
# playbook.yml
- hosts: all
  become: yes
  tasks:
    - name: Install nginx
      apt:
        name: nginx
        state: present

    - name: Deploy index page
      copy:
        content: "<h1>Hello</h1>"
        dest: /var/www/html/index.html

    - name: Ensure nginx is running
      systemd:
        name: nginx
        state: started
        enabled: yes
```

#### Étape 4 : constater

Les trois font la même chose. La version manuelle est la plus rapide pour un serveur.
Mais pour 10 serveurs identiques ? Pour reconstruire après un crash ?

### Progression recommandée

```
Niveau 1 : Dockerfile simple
  └─ Un service, FROM + COPY + RUN
  └─ Objectif : construire une image qui marche

Niveau 2 : Docker Compose
  └─ Plusieurs services qui communiquent
  └─ Objectif : app + base de données + cache

Niveau 3 : Dockerfile multi-stage
  └─ Build vs runtime, optimisation
  └─ Objectif : image de production légère

Niveau 4 : Ansible basics
  └─ Installer des paquets, copier des fichiers, gérer des services
  └─ Objectif : provisionner une VM complète

Niveau 5 : Ansible rôles
  └─ Organisation modulaire, variables, handlers
  └─ Objectif : infrastructure réutilisable

Niveau 6 : Terraform
  └─ Créer des ressources cloud (VM, réseau, DNS)
  └─ Objectif : infrastructure reproductible
```

### Ce qu'il faut éviter

- **Copier-coller un Dockerfile/playbook complet depuis internet** : tu n'apprendras rien. Écris-le ligne par ligne.
- **Vouloir tout automatiser d'un coup** : commence par un service, puis ajoutes-en un.
- **Ignorer les erreurs** : chaque erreur est une leçon. Lis le message, comprends-le, corrige.
- **Chercher la perfection** : un Dockerfile qui marche vaut mieux qu'un Dockerfile parfait qui n'existe pas.

---

## 0.5 Débugger l'infrastructure

### Docker : les commandes de diagnostic

```bash
# Voir les logs d'un container
docker logs <container>
docker logs -f <container>           # Suivre en temps réel
docker compose logs php              # Via compose

# Entrer dans un container qui tourne
docker exec -it <container> /bin/sh

# Inspecter un container (réseau, volumes, config)
docker inspect <container>

# Voir les layers d'une image (comprendre ce qui prend de la place)
docker history <image>

# Voir les processus dans un container
docker top <container>

# Voir l'utilisation des ressources
docker stats
```

### Ansible : les options de debug

```bash
# Verbosité croissante
ansible-playbook playbook.yml -v      # Résultat des tâches
ansible-playbook playbook.yml -vv     # Détails des connexions
ansible-playbook playbook.yml -vvv    # Tous les détails

# Mode check : voir ce qui changerait sans rien faire
ansible-playbook playbook.yml --check --diff

# Exécuter une seule tâche
ansible-playbook playbook.yml --start-at-task="Install nginx"

# Demander confirmation pour chaque tâche
ansible-playbook playbook.yml --step
```

### La méthode générale de debug

```
1. Lire le message d'erreur EN ENTIER (pas juste la dernière ligne)
2. Identifier QUEL outil produit l'erreur (Docker, Ansible, le service lui-même ?)
3. Reproduire l'erreur manuellement (entrer dans le container, taper la commande)
4. Corriger dans le fichier d'automatisation
5. Rejouer depuis zéro (destroy + rebuild) pour vérifier
```

---

## 0.6 - Ressources recommandées

### Pour Docker

- **Doc officielle** : https://docs.docker.com/get-started/ (le Getting Started est bien fait)
- **Dockerfile best practices** : https://docs.docker.com/build/building/best-practices/
- **Play with Docker** : https://labs.play-with-docker.com (sandbox en ligne, rien à installer)

### Pour Ansible

- **Doc officielle** : https://docs.ansible.com/ansible/latest/getting_started/
- **Module index** : https://docs.ansible.com/ansible/latest/collections/ansible/builtin/ (tous les modules built-in)
- **Ansible Galaxy** : https://galaxy.ansible.com (rôles communautaires pour s'inspirer)

### Pour Terraform

- **Learn Terraform** : https://developer.hashicorp.com/terraform/tutorials (tutoriels interactifs)
- **Registry** : https://registry.terraform.io (documentation de tous les providers)

### Approche générale

- Lire la doc du module/directive **avant** de l'utiliser
- Tester **une chose à la fois**
- Versionner ses fichiers d'infra avec Git dès le début
- Commiter à chaque étape qui fonctionne (on peut revenir en arrière)

---

## Checklist de fin de phase

- [ ] Capable de lire un Dockerfile et comprendre chaque instruction
- [ ] Capable d'écrire un Dockerfile simple depuis zéro
- [ ] Environnement de test local fonctionnel (Docker, Vagrant, ou VM)
- [ ] Au moins un playbook Ansible testé sur un environnement jetable
- [ ] Réflexe de chercher dans la doc officielle avant tout
- [ ] Comprendre la différence entre impératif et déclaratif
