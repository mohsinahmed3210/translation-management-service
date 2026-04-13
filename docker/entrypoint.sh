#!/bin/sh
set -e

echo "==> Installing/verifying composer dependencies..."
php -d memory_limit=-1 /usr/bin/composer update --optimize-autoloader --no-interaction --quiet

echo "==> Ensuring storage directories exist..."
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache/data \
         storage/logs \
         bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Waiting for MySQL to accept connections..."
until php artisan migrate:status > /dev/null 2>&1; do
    echo "   MySQL not ready yet, retrying in 3s..."
    sleep 3
done

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding base tags..."
php artisan db:seed --class=TagSeeder --force

echo "==> Caching config and routes..."
php artisan config:cache
php artisan route:cache

echo "==> Generating Swagger documentation..."
php artisan l5-swagger:generate

echo "==> Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
