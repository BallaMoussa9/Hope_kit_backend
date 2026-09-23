#!/bin/sh
set -e

echo "========================================"
echo " HOPE HEALTH AND CARE"
echo " Starting application"
echo "========================================"

php artisan optimize:clear

mkdir -p \
    storage/app/public \
    storage/app/public/ivr \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

php artisan migrate --force
php artisan optimize

# In production (Render), this can be enabled with RUN_QUEUE_WORKER=true.
# With docker-compose, the dedicated queue_worker service should be used instead.
if [ "${RUN_QUEUE_WORKER:-false}" = "true" ]; then
    echo "Starting database queue worker..."
    php artisan queue:work database --sleep=3 --tries=3 --timeout=120 --queue=default &
fi

if [ "${RUN_SCHEDULER:-false}" = "true" ]; then
    echo "Starting Laravel scheduler..."
    php artisan schedule:work &
fi

php-fpm -D

if [ -f /etc/nginx/conf.d/default.conf ]; then
    sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/conf.d/default.conf
    if [ -z "${PHP_FPM_HOST:-}" ]; then
        if [ "${PORT:-80}" != "80" ]; then
            PHP_FPM_HOST=127.0.0.1
        else
            PHP_FPM_HOST=app
        fi
    fi
    sed -i "s/fastcgi_pass app:9000;/fastcgi_pass ${PHP_FPM_HOST}:9000;/" /etc/nginx/conf.d/default.conf
fi

echo "========================================"
echo " HOPE application started successfully"
echo "========================================"

exec nginx -g "daemon off;"
