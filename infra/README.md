# Infrastructure Docker - Projet POO

Environnement de développement local avec Docker pour le projet PHP 8.4.

## Stack

- **Nginx** - Serveur web avec support HTTPS
- **PHP 8.4-FPM** - Avec Xdebug pour le débogage
- **MySQL 9** - Base de données
- **Redis** - Cache et stockage en mémoire

## Prérequis

- Docker et Docker Compose installés
- mkcert pour les certificats SSL

### Installation de mkcert

**Linux (Debian/Ubuntu):**
```bash
sudo apt install libnss3-tools
curl -L https://github.com/FiloSottile/mkcert/releases/download/v1.4.4/mkcert-v1.4.4-linux-amd64 -o mkcert
chmod +x mkcert
sudo mv mkcert /usr/local/bin/
```

**macOS:**
```bash
brew install mkcert
```

**Windows (avec Chocolatey):**
```bash
choco install mkcert
```

## Installation

### 1. Générer les certificats SSL

```bash
cd infra/ssl
./generate-cert.sh
```

Ce script va:
- Installer l'autorité de certification locale de mkcert
- Générer les certificats pour `poo.localhost`
- Les certificats seront automatiquement approuvés par votre navigateur

### 2. Ajouter le domaine au fichier hosts

Ajoutez cette ligne à votre fichier `/etc/hosts` (Linux/macOS) ou `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 poo.localhost
```

### 3. Démarrer l'environnement

```bash
cd infra
docker-compose up -d
```

La première fois, Docker va construire l'image PHP avec Xdebug, cela peut prendre quelques minutes.

## Accès aux services

- **Application web**: https://poo.localhost
- **MySQL**: localhost:3306
  - User: `poo_user`
  - Password: `poo_password`
  - Database: `poo_project`
  - Root password: `root`
- **Redis**: localhost:6389

## Commandes utiles

### Démarrer les conteneurs
```bash
cd infra
docker-compose up -d
```

### Arrêter les conteneurs
```bash
cd infra
docker-compose down
```

### Voir les logs
```bash
cd infra
docker-compose logs -f
```

### Reconstruire l'image PHP (après modification du Dockerfile)
```bash
cd infra
docker-compose build php
docker-compose up -d
```

### Accéder au conteneur PHP
```bash
docker exec -it poo-php sh
```

### Accéder à MySQL
```bash
docker exec -it poo-mysql mysql -u poo_user -ppoo_password poo_project
```

### Accéder à Redis CLI
```bash
docker exec -it poo-redis redis-cli
```

## Configuration Xdebug

Xdebug est préconfiguré et écoute sur le port `9003`.

### Configuration PHPStorm/IntelliJ

1. Aller dans `Settings > PHP > Servers`
2. Ajouter un nouveau serveur:
   - Name: `poo.localhost`
   - Host: `poo.localhost`
   - Port: `443`
   - Debugger: `Xdebug`
   - Use path mappings: ✓
     - Mapper le dossier du projet vers `/var/www/html`

3. Activer "Start listening for PHP Debug Connections"
4. Placer un breakpoint et rafraîchir la page

### Configuration VS Code

Ajouter cette configuration dans `.vscode/launch.json`:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}"
      }
    }
  ]
}
```

## Structure des fichiers

```
infra/
├── docker-compose.yml          # Configuration des services
├── nginx/
│   └── default.conf           # Configuration Nginx avec HTTPS
├── php/
│   ├── Dockerfile             # Image PHP 8.4 avec Xdebug
│   └── php.ini                # Configuration PHP personnalisée
├── ssl/
│   ├── generate-cert.sh       # Script de génération des certificats
│   ├── poo.localhost.pem      # Certificat SSL (généré)
│   └── poo.localhost-key.pem  # Clé privée SSL (générée)
└── mysql/
    └── init/                  # Scripts SQL d'initialisation (optionnel)
```

## Données persistantes

Les données MySQL et Redis sont stockées dans des volumes Docker :
- `mysql-data`: Données MySQL
- `redis-data`: Données Redis

Pour supprimer toutes les données :
```bash
cd infra
docker-compose down -v
```

## Résolution de problèmes

### Le site n'est pas accessible
- Vérifiez que les conteneurs sont bien démarrés: `docker-compose ps`
- Vérifiez que le domaine est bien dans `/etc/hosts`
- Vérifiez les logs: `docker-compose logs nginx php`

### Certificat SSL non reconnu
- Exécutez à nouveau `./ssl/generate-cert.sh`
- Vérifiez que mkcert est bien installé: `mkcert -version`
- Redémarrez votre navigateur

### Xdebug ne fonctionne pas
- Vérifiez que Xdebug est bien installé: `docker exec poo-php php -v`
- Vérifiez les logs Xdebug: `docker exec poo-php cat /tmp/xdebug.log`
- Vérifiez que votre IDE écoute sur le port 9003

### Erreur de permissions sur les fichiers
Les fichiers PHP sont exécutés avec l'utilisateur `www` (UID 1000). Si vous avez des problèmes de permissions :
```bash
sudo chown -R 1000:1000 ../src ../public ../app ../config ../database
```
