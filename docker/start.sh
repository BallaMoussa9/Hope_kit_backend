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
fi

echo "========================================"
echo " HOPE application started successfully"
echo "========================================"

exec nginx -g "daemon off;"
