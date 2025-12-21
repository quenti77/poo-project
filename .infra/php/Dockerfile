FROM php:8.5-fpm-alpine

# Install dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    linux-headers \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    xml

# Install Redis extension
RUN pecl install redis && \
    docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Configure Xdebug
COPY ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Set working directory
WORKDIR /var/www/html

# Create user for running php-fpm
RUN addgroup -g 1000 www && \
    adduser -D -u 1000 -G www www

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
