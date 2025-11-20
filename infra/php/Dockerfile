FROM php:8.4-fpm-alpine

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
    opcache \
    xml

# Install Redis extension
RUN pecl install redis && \
    docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Set working directory
WORKDIR /var/www/html

# Create user for running php-fpm
RUN addgroup -g 1000 www && \
    adduser -D -u 1000 -G www www

# Change ownership
RUN chown -R www:www /var/www/html

# Switch to www user
USER www

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
