FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring bcmath intl zip gd opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
# Install dependencies before copying the application, without running Laravel scripts.
# Laravel's package:discover needs artisan, which is copied only with the application.
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/hope.ini

EXPOSE 9000
CMD ["php-fpm"]
