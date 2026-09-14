#!/bin/sh

set -e

echo "Starting PHP-FPM..."

php-fpm -D

echo "Starting Nginx on port ${PORT:-80}..."

sed -i "s/listen 80;/listen ${PORT:-80};/" /etc/nginx/conf.d/default.conf

exec nginx -g "daemon off;"
