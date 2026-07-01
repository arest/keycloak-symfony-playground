FROM php:8.3-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    git \
    unzip \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    mbstring \
    xml \
    curl \
    opcache \
    zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/symfony
