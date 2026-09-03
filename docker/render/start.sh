#!/bin/sh

set -e

cd /var/www/html

echo "=========================================="
echo " HOPE API - démarrage Render"
echo "=========================================="

php artisan --version

echo ">> Migration de la base..."
php artisan migrate --force

echo ">> Création du lien storage..."
php artisan storage:link || true

echo ">> Cache Laravel..."
php artisan config:cache
php artisan route:cache

echo ">> Démarrage PHP-FPM..."
php-fpm -D

echo ">> Démarrage Nginx..."
nginx -g "daemon off;"
