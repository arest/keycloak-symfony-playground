FROM php:8.3-fpm-alpine

# System dependencies for PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    libxml2-dev \
    curl-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-install -j$(nproc) \
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
