FROM php:8.5-fpm AS base-php

# Regarder le fichier .env à la racine du projet
ARG UID=1000
ARG GID=1000

# Installation des dépendances système
RUN apt-get update -y && \
    apt-get upgrade -y && \
    apt-get install -y \
        git \
        unzip \
        libzip-dev \
        libpq-dev && \
    docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip

# Installer Redis
RUN pecl install redis && docker-php-ext-enable redis

# Récupérer composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Créer un groupe et un utilisateur avec les bons UID et GID
RUN groupadd -g ${GID} app_user && \
    useradd -u ${UID} -g app_user -m app_user

# Indiquer le répoertoire de travail
WORKDIR /var/www

# Copier les fichiers de l'application
COPY app/ .

EXPOSE 9000
CMD ["php-fpm", "-F"]


FROM base-php AS local-php

# Installation de XDebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY infra/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Installation de toutes les dépendances
RUN composer install --no-interaction --prefer-dist

RUN chown -R app_user:app_user /var/www

USER app_user


FROM base-php AS production-php

# Installation des dépendances sans les versions de dev
RUN composer install --no-interaction --no-dev --prefer-dist

# Suppression des packages non nécessaire
RUN apt-get clean && rm -rf /var/lib/apt/lists/* && rm /usr/bin/composer

# Optimisation Laravel
RUN php artisan config:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

RUN chown -R app_user:app_user /var/www

USER app_user
