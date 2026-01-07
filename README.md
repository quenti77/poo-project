# POO Project

Projet d'exemple de création d'un Framework complet sans utilisation de l'outil `composer`.
Le but est de reproduire le plus possible ce qui fait la force des frameworks. En voici une liste :

- **Dependency Injection Container** : Permet d'avoir accès à nos instances de manière globale et dynamique via la `Reflection`.
- **HTTP** : Représente la partie `Request` et `Response` de l'application.
- **Router** : Permet de choisir la bonne action (contrôleur, fonction) à exécuter en fonction de la méthode et de l'URL.
- **Middleware** : En complément du router, permet d'effectuer des actions ou vérifications avant ou après l'action.
- **Logger** : Permet d'enregistrer les informations sur le cycle de vie de l'application (debug, info, warning, error, ...).
- **Error manager** : Capture les erreurs et exceptions non géré pour fournir un message HTML, JSON et un log.
- **Database** : Connection à une base de données et petit Query Builder pour simplifier des requêtes classiques.
- **Migrations & Seeders** : Permet la création des tables et l'injection de données.
- **Translation** : Gestion des fichiers de traductions.
- **CLI** : Permet la création de commandes dans le terminal.
- **Queue** : Permet d'effectuer les tâches dans d'autres processus séparés.
- **Event** : Permet de prévenir les différents contextes de ce qui s'est passé.
- **Cache** : Gestion du cache dans le projet

## Stack technique du projet

Ce projet comme indiqué, n'utilise pas `composer` ni de package externe. Par contre, il peut utiliser docker si
vous n'avez pas envie d'installer tous les éléments nécessaire au bon fonctionnement de l'application.
Voici les prérequis :

- **PHP 8.5**
- **Mysql 9** (mais on peut utiliser PostgreSQL)
- **Redis** (pas obligatoire)

Si vous voulez lancer le docker, il faudra installer `mkcert` ([Lien du Github](https://github.com/FiloSottile/mkcert)),
puis lancer la commande : `php .infra/generate-cert.php`. C'est nécessaire pour créer des certificats SSL pour le nginx
dans le container.
