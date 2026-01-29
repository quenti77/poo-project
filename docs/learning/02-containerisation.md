# Phase 2 : Containerisation

Cette phase transforme l'application en containers Docker reproductibles
et met en place les premiers éléments de CI.

---

## 2.1 Docker - Concepts fondamentaux

### Pourquoi Docker

- **Reproductibilité** : même environnement partout (dev, CI, prod)
- **Isolation** : chaque service dans son container
- **Portabilité** : fonctionne sur n'importe quelle machine avec Docker

### Vocabulaire essentiel

| Terme          | Description                                            |
|----------------|--------------------------------------------------------|
| **Image**      | Template immuable pour créer des containers            |
| **Container**  | Instance en cours d'exécution d'une image              |
| **Dockerfile** | Fichier de recette pour construire une image           |
| **Layer**      | Couche de l'image (chaque instruction = 1 layer)       |
| **Volume**     | Stockage persistant externe au container               |
| **Network**    | Réseau virtuel pour connecter les containers           |
| **Registry**   | Dépôt d'images (Docker Hub, GitHub Container Registry) |

### Architecture Docker

```
┌─────────────────────────────────────────────────────────┐
│                    Docker Host                          │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│   │  Container  │  │  Container  │  │  Container  │     │
│   │    nginx    │  │   php-fpm   │  │  postgres   │     │
│   └──────┬──────┘  └──────┬──────┘  └──────┬──────┘     │
│          │                │                │            │
│          └────────────────┼────────────────┘            │
│                           │                             │
│                    ┌──────┴──────┐                      │
│                    │   Network   │                      │
│                    │   (bridge)  │                      │
│                    └─────────────┘                      │
│                                                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │                    Volumes                       │   │
│  │  db_data         app_storage        redis_data   │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

---

## 2.2 Dockerfile PHP - Multi-stage

### Structure multi-stage

Le multi-stage permet de :
- Séparer build et runtime
- Réduire la taille de l'image finale
- Avoir des images différentes pour dev et prod

```dockerfile
# infra/docker/php/Dockerfile

# =============================================================================
# Stage 1 : Base commune
# =============================================================================
FROM php:8.3-fpm-alpine AS base

# Extensions PHP nécessaires
RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        zip \
        intl \
        opcache \
        pcntl

# Configuration PHP
COPY infra/docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY infra/docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# =============================================================================
# Stage 2 : Dépendances Composer
# =============================================================================
FROM base AS composer-deps

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier uniquement les fichiers de dépendances
COPY app/composer.json app/composer.lock ./

# Installer les dépendances (sans dev pour prod)
ARG APP_ENV=production
RUN if [ "$APP_ENV" = "production" ]; then \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    else \
        composer install --no-scripts --no-autoloader --prefer-dist; \
    fi

# =============================================================================
# Stage 3 : Build des assets (Node)
# =============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

# Copier les fichiers de dépendances Node
COPY app/package.json app/package-lock.json ./

# Installer les dépendances
RUN npm ci

# Copier les sources pour le build
COPY app/resources ./resources
COPY app/vite.config.js ./
COPY app/tsconfig.json ./

# Build de production
RUN npm run build

# =============================================================================
# Stage 4 : Image de développement
# =============================================================================
FROM base AS development

# Outils de développement
RUN apk add --no-cache git

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Xdebug pour le debug
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY infra/docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# L'utilisateur non-root
RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app
USER app

CMD ["php-fpm"]

# =============================================================================
# Stage 5 : Image de production
# =============================================================================
FROM base AS production

# Copier les dépendances Composer
COPY --from=composer-deps /var/www/html/vendor ./vendor

# Copier les assets buildés
COPY --from=node-builder /app/public/build ./public/build

# Copier le code source
COPY app/ ./

# Optimisations Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

CMD ["php-fpm"]
```

### Configuration PHP optimisée

```ini
; infra/docker/php/php.ini

[PHP]
; Performances
memory_limit = 256M
max_execution_time = 30
max_input_time = 60

; Upload
upload_max_filesize = 50M
post_max_size = 50M

; Erreurs (désactivé en prod)
display_errors = Off
log_errors = On
error_log = /dev/stderr

; OPcache (production)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.save_comments = 1

; Realpath cache
realpath_cache_size = 4096K
realpath_cache_ttl = 600

[Date]
date.timezone = UTC

[Session]
session.save_handler = redis
session.save_path = "tcp://redis:6379"
```

### Configuration PHP-FPM

```ini
; infra/docker/php/php-fpm.conf

[www]
user = www-data
group = www-data

listen = 0.0.0.0:9000

; Process manager
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Logs
access.log = /dev/stdout
slowlog = /dev/stderr
request_slowlog_timeout = 5s

; Status (pour monitoring)
pm.status_path = /status
ping.path = /ping
ping.response = pong

; Clear env for security
clear_env = no
```

---

## 2.3 Dockerfile Nginx

```dockerfile
# infra/docker/nginx/Dockerfile

FROM nginx:1.25-alpine AS base

# Supprimer la config par défaut
RUN rm /etc/nginx/conf.d/default.conf

# Configuration nginx
COPY infra/docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY infra/docker/nginx/app.conf /etc/nginx/conf.d/app.conf

WORKDIR /var/www/html

# =============================================================================
# Production : inclure les assets statiques
# =============================================================================
FROM base AS production

# Copier les assets depuis le builder Node
COPY --from=node-builder /app/public ./public

# Copier les assets Laravel (favicon, robots.txt, etc.)
COPY app/public ./public
```

### Configuration Nginx

```nginx
# infra/docker/nginx/nginx.conf

user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # Logging
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    access_log /var/log/nginx/access.log main;

    # Performances
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;

    # Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript
               application/xml application/xml+rss text/javascript;

    # Sécurité
    server_tokens off;

    include /etc/nginx/conf.d/*.conf;
}
```

```nginx
# infra/docker/nginx/app.conf

server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    # Sécurité headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Assets statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
    }

    # Bloquer l'accès aux fichiers cachés
    location ~ /\. {
        deny all;
    }

    # Health check
    location /health {
        access_log off;
        return 200 "OK";
        add_header Content-Type text/plain;
    }
}
```

---

## 2.4 Docker Compose

### Environnement de développement

```yaml
# docker-compose.yml (développement)

services:
  # ==========================================================================
  # Nginx - Reverse Proxy
  # ==========================================================================
  nginx:
    build:
      context: .
      dockerfile: infra/docker/nginx/Dockerfile
      target: base
    ports:
      - "${APP_PORT:-80}:80"
    volumes:
      - ./app/public:/var/www/html/public:ro
    depends_on:
      - php
    networks:
      - app-network

  # ==========================================================================
  # PHP-FPM
  # ==========================================================================
  php:
    build:
      context: .
      dockerfile: infra/docker/php/Dockerfile
      target: development
      args:
        APP_ENV: local
    volumes:
      - ./app:/var/www/html
      - composer-cache:/home/app/.composer
    environment:
      APP_ENV: local
      APP_DEBUG: "true"
      XDEBUG_MODE: "${XDEBUG_MODE:-off}"
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - app-network

  # ==========================================================================
  # Node - Vite Dev Server
  # ==========================================================================
  node:
    image: node:22-alpine
    working_dir: /app
    volumes:
      - ./app:/app
      - node-modules:/app/node_modules
    command: npm run dev -- --host
    ports:
      - "${VITE_PORT:-5173}:5173"
    networks:
      - app-network

  # ==========================================================================
  # PostgreSQL
  # ==========================================================================
  db:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ${DB_DATABASE:-app}
      POSTGRES_USER: ${DB_USERNAME:-app}
      POSTGRES_PASSWORD: ${DB_PASSWORD:-secret}
    volumes:
      - db-data:/var/lib/postgresql/data
    ports:
      - "${DB_PORT:-5432}:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-app}"]
      interval: 5s
      timeout: 5s
      retries: 5
    networks:
      - app-network

  # ==========================================================================
  # Redis
  # ==========================================================================
  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes
    volumes:
      - redis-data:/data
    ports:
      - "${REDIS_PORT:-6379}:6379"
    networks:
      - app-network

  # ==========================================================================
  # Mailpit (dev mail catcher)
  # ==========================================================================
  mailpit:
    image: axllent/mailpit
    ports:
      - "${MAILPIT_PORT:-8025}:8025"
      - "1025:1025"
    networks:
      - app-network

networks:
  app-network:
    driver: bridge

volumes:
  db-data:
  redis-data:
  composer-cache:
  node-modules:
```

### Environnement de production (simple)

```yaml
# docker-compose.production.yml

services:
  nginx:
    build:
      context: .
      dockerfile: infra/docker/nginx/Dockerfile
      target: production
    ports:
      - "80:80"
    depends_on:
      - php
    networks:
      - app-network
    restart: unless-stopped

  php:
    build:
      context: .
      dockerfile: infra/docker/php/Dockerfile
      target: production
      args:
        APP_ENV: production
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
    env_file:
      - .env.production
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - app-network
    restart: unless-stopped

  db:
    image: postgres:16-alpine
    env_file:
      - .env.production
    volumes:
      - db-data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U $$POSTGRES_USER"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - app-network
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis-data:/data
    networks:
      - app-network
    restart: unless-stopped

networks:
  app-network:
    driver: bridge

volumes:
  db-data:
  redis-data:
```

---

## 2.5 Optimisation des images

### .dockerignore

```dockerignore
# .dockerignore

# Git
.git
.gitignore

# Documentation
docs/
*.md

# IDE
.idea/
.vscode/

# Tests
tests/
phpunit.xml
.phpunit.cache/

# Dev dependencies
node_modules/

# Caches
.php-cs-fixer.cache
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*

# Env files (sauf example)
.env
.env.*
!.env.example

# Docker
docker-compose*.yml
infra/

# CI/CD
.github/
.gitlab-ci.yml
```

### Bonnes pratiques de cache

```dockerfile
# MAUVAIS - Invalide le cache à chaque changement de code
COPY . /app
RUN composer install

# BON - Copie d'abord les fichiers de dépendances
COPY composer.json composer.lock /app/
RUN composer install --no-scripts
COPY . /app
RUN composer dump-autoload
```

### Réduire la taille

```dockerfile
# Utiliser alpine
FROM php:8.3-fpm-alpine
# ~50MB vs ~400MB pour debian

# Nettoyer après installation
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo_pgsql \
    && apk del postgresql-dev  # Supprimer après compilation

# Multi-stage : ne garder que le nécessaire
COPY --from=builder /app/vendor ./vendor
# Pas besoin de composer en prod
```

---

## 2.6 CI basique

### GitHub Actions

```yaml
# .github/workflows/ci.yml

name: CI

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  # ==========================================================================
  # Qualité du code
  # ==========================================================================
  quality:
    name: Code Quality
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_pgsql, redis, zip, intl
          coverage: xdebug

      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('app/composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        working-directory: app
        run: composer install --prefer-dist --no-progress

      - name: PHP-CS-Fixer
        working-directory: app
        run: vendor/bin/php-cs-fixer fix --dry-run --diff

      - name: PHPStan
        working-directory: app
        run: vendor/bin/phpstan analyse

  # ==========================================================================
  # Tests
  # ==========================================================================
  tests:
    name: Tests
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16-alpine
        env:
          POSTGRES_DB: testing
          POSTGRES_USER: testing
          POSTGRES_PASSWORD: testing
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

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
          extensions: pdo_pgsql, redis, zip, intl
          coverage: xdebug

      - name: Install dependencies
        working-directory: app
        run: composer install --prefer-dist --no-progress

      - name: Prepare environment
        working-directory: app
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run tests
        working-directory: app
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_DATABASE: testing
          DB_USERNAME: testing
          DB_PASSWORD: testing
          REDIS_HOST: localhost
        run: vendor/bin/phpunit --coverage-clover coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v4
        with:
          files: app/coverage.xml

  # ==========================================================================
  # Build Docker
  # ==========================================================================
  build:
    name: Build Docker Image
    runs-on: ubuntu-latest
    needs: [quality, tests]

    steps:
      - uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Build image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: infra/docker/php/Dockerfile
          target: production
          push: false
          tags: app:${{ github.sha }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

### GitLab CI

```yaml
# .gitlab-ci.yml

stages:
  - quality
  - test
  - build

variables:
  POSTGRES_DB: testing
  POSTGRES_USER: testing
  POSTGRES_PASSWORD: testing

# ==============================================================================
# Templates
# ==============================================================================
.php-template:
  image: php:8.3-cli-alpine
  before_script:
    - apk add --no-cache postgresql-dev icu-dev libzip-dev
    - docker-php-ext-install pdo_pgsql intl zip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - cd app && composer install --prefer-dist --no-progress
  cache:
    key: composer-$CI_COMMIT_REF_SLUG
    paths:
      - app/vendor/

# ==============================================================================
# Jobs
# ==============================================================================
lint:
  extends: .php-template
  stage: quality
  script:
    - cd app && vendor/bin/php-cs-fixer fix --dry-run --diff

analyse:
  extends: .php-template
  stage: quality
  script:
    - cd app && vendor/bin/phpstan analyse

test:
  extends: .php-template
  stage: test
  services:
    - postgres:16-alpine
    - redis:7-alpine
  variables:
    DB_CONNECTION: pgsql
    DB_HOST: postgres
    DB_DATABASE: testing
    DB_USERNAME: testing
    DB_PASSWORD: testing
    REDIS_HOST: redis
  script:
    - cd app
    - cp .env.example .env
    - php artisan key:generate
    - vendor/bin/phpunit --coverage-text
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'

build:
  stage: build
  image: docker:24
  services:
    - docker:24-dind
  needs:
    - lint
    - analyse
    - test
  script:
    - docker build -f infra/docker/php/Dockerfile --target production -t app:$CI_COMMIT_SHA .
  rules:
    - if: $CI_COMMIT_BRANCH == "main"
```

---

## 2.7 Documentation d'exploitation

Créer `docs/local-development.md` :

```markdown
# Développement local

## Prérequis

- Docker Engine 24+
- Task (taskfile.dev)
- Git

## Premier lancement

\`\`\`bash
# Cloner le projet
git clone <repo>
cd project

# Initialiser l'environnement
task init

# Lancer les services
task up

# Installer les dépendances
task composer install
task npm install
\`\`\`

Accéder à l'application : http://localhost

## Commandes courantes

\`\`\`bash
# Voir toutes les commandes
task -l

# Logs
task logs           # Tous les services
task logs -- php    # Un service spécifique

# Shell dans un container
task shell          # PHP container
task shell -- db    # PostgreSQL

# Base de données
task artisan migrate
task artisan migrate:fresh --seed

# Tests
task test
task test:coverage

# Qualité
task lint
task lint:fix
task analyse
\`\`\`

## Arrêt et nettoyage

\`\`\`bash
# Arrêter les services
task down

# Arrêter et supprimer les volumes
task down -- -v

# Rebuild complet
task rebuild
\`\`\`

## Xdebug

Pour activer Xdebug :

\`\`\`bash
XDEBUG_MODE=debug task up
\`\`\`

Configuration IDE (VS Code) :

\`\`\`json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}/app"
            }
        }
    ]
}
\`\`\`

## Erreurs courantes

### Container ne démarre pas

\`\`\`bash
# Vérifier les logs
docker compose logs php

# Causes fréquentes :
# - Port déjà utilisé : changer dans `.env`
# - Permissions : vérifier les droits sur storage/
\`\`\`

### Base de données inaccessible

\`\`\`bash
# Vérifier que le service est healthy
docker compose ps

# Se connecter manuellement
task shell -- db
psql -U app -d app
\`\`\`

### Assets non chargés

\`\`\`bash
# Vérifier que Vite tourne
docker compose logs node

# Rebuild des assets
task npm run build
\`\`\`
```

---

## Checklist de fin de phase

- [ ] Dockerfile PHP multi-stage fonctionnel
- [ ] Dockerfile Nginx configuré
- [ ] Docker Compose dev avec tous les services
- [ ] Docker Compose prod simplifié
- [ ] .dockerignore optimisé
- [ ] CI configuré (lint + tests + build)
- [ ] Documentation d'exploitation complète
- [ ] `task up` lance le projet en une commande
