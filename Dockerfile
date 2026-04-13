FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        git \
        zip \
        unzip \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev

# Install PHP extensions
RUN docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        bcmath \
        opcache \
        intl

# Install phpredis extension via PECL so REDIS_CLIENT=phpredis works in production.
# The docker-compose env explicitly sets REDIS_CLIENT=phpredis to use this.
RUN pecl install redis && docker-php-ext-enable redis

# PHP configuration
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/custom.ini

# Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint: runs migrations then starts Supervisor
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
