FROM php:8.2-fpm AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    nano \
    libzip-dev \
    libpq-dev \
    gdal-bin \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip

RUN pecl install redis && docker-php-ext-enable redis

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

FROM base AS dev

# Set permissions
RUN chown -R www-data:www-data /var/www/html

FROM base AS prod

# Install Composer (single copy for all stages)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application code after dependencies
COPY . .

# Install PHP dependencies (no dev dependencies)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Set ownership and permissions in the same layer to reduce image size
RUN chown -R www-data:www-data /var/www/html

# Optionally remove dev tools
RUN apt-get purge -y nano git && apt-get autoremove -y && apt-get clean