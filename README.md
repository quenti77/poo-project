# POO-Project (version Infrastructure)

Cette version du projet, permet l'apprentissage et la mise en pratique de la création d'un projet jusqu'à son
déploiement en production. Cela va utiliser Laravel + Inertia en remplacement du code fait maison. Pour des soucis
de simplicité et de problème potentiel.

<!-- TOC -->
* [POO-Project (version Infrastructure)](#poo-project-version-infrastructure)
  * [Objectifs](#objectifs)
  * [Stack technique](#stack-technique)
  * [Pré-requis](#pré-requis)
  * [Installation et lancement](#installation-et-lancement)
  * [État du projet](#état-du-projet)
<!-- TOC -->

## Objectifs

- Apprendre l'écriture de documentation
- Mettre en place une infra reproductible (Pulumi, Ansible, CI/CD)
- Déployer une application Laravel avec Kubernetes (k8s)


## Stack technique

- Backend : [PHP](https://www.php.net/releases/8.5/en.php) et [Laravel](https://laravel.com/docs/12.x)
- Frontend : [Inertia.js](https://inertiajs.com/docs/v2/getting-started/index) avec [Vue.js](https://vuejs.org/guide/introduction.html)
- Container : [Docker](https://docs.docker.com/get-started/)
- Orchestrator : [Kubernetes](https://kubernetes.io/docs/home/) (abrégé **k8s**)
- Infra as Code (IaC) : [Pulumi](https://www.pulumi.com/docs/)
- Configuration : [Ansible](https://docs.ansible.com)


## Pré-requis

Voici les versions minimum des outils à avoir :

- PHP version 8.5
- Vue.js version 3.5
- Docker engine version 29
- Un environnement Node :
  - Node.js version 24
  - Yarn version 1.22
  - pnpm version 10
  - ⚠️ Bun.js (pas complètement compatible)
- TaskFile (Remplace MakeFile)
- mkcert (installé automatiquement par `task init` si manquant)


## Installation et lancement

Les commandes suivantes permettent de lancer le projet :

```bash
# Récupère le projet
git clone https://github.com/quenti77/poo-project
cd poo-project

# Compile et lance le projet
task init    # Crée les fichiers .env et certificats SSL
task build   # Compile les images Docker
task up      # Lance les containers (génère APP_KEY si manquante)
```

Ajouter `poo.test` dans `/etc/hosts` :

```bash
echo "127.0.0.1 poo.test" | sudo tee -a /etc/hosts
```

Accéder au projet : https://poo.test

Vous pouvez utiliser ces commandes :

```bash
task -l       # Voir la liste des commandes
task down     # Arrêter le projet
task logs     # Voir les logs
```


## État du projet

🚧 En cours de développement

- [ ] Avoir une infra potable pour le dev
