#!/bin/sh
set -e

echo "========================================"
echo " HOPE - Starting application"
echo "========================================"

echo "Clearing Laravel configuration cache..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo "========================================"
echo " Running Laravel database migrations"
echo "========================================"

php artisan migrate --force

echo "========================================"
echo " Laravel migrations completed"
echo "========================================"

echo "Starting PHP-FPM..."

php-fpm -D

echo "Starting Nginx on port ${PORT:-80}..."

if [ -f /etc/nginx/conf.d/default.conf ]; then
    sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/conf.d/default.conf
    # Docker Compose: PHP-FPM is the `app` service. Render: PHP-FPM runs in this same container.
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
