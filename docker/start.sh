#!/bin/sh

set -e

echo "======================================"
echo "Starting Hope Kit Backend"
echo "======================================"

echo "PORT=${PORT:-10000}"

php artisan config:cache
php artisan route:cache

php artisan view:cache || true

echo "Starting PHP-FPM..."

php-fpm -D

echo "Starting Nginx..."

nginx -g "daemon off;"
