#!/bin/sh
set -e

APP_DIR="/var/www/html"
cd "$APP_DIR"

# ---------------------------------------------------------------------------
# Seul le conteneur "app" installe/maintient le projet Laravel. Les autres
# rôles (queue, scheduler) attendent simplement que ce soit prêt, pour éviter
# que plusieurs conteneurs n'écrivent en même temps dans le volume partagé.
# ---------------------------------------------------------------------------
if [ "$CONTAINER_ROLE" = "app" ]; then

    # Désactive le blocage "policy.advisories" de Composer (nouvelle
    # fonctionnalité qui refuse d'installer certaines versions de paquets
    # concernées par un avis de sécurité, même connu et déjà patché en
    # amont). --no-audit a été supprimé car cette option n'est pas supportée
    # par la version de Composer utilisée dans le conteneur.
    composer config -g policy.advisories.block false 2>/dev/null || true

    # 1) Squelette Laravel absent -> on l'installe
    if [ ! -f "$APP_DIR/artisan" ]; then
        echo ">> Aucun projet Laravel détecté — installation initiale..."

        rm -rf tmp_install

        composer create-project laravel/laravel:^11.0 tmp_install \
            --prefer-dist --no-interaction
        cp -r tmp_install/. .
        rm -rf tmp_install
        echo ">> Squelette Laravel installé."
    fi

    # 2) Sanctum / Fortify absents -> on les ajoute
    if [ ! -d "$APP_DIR/vendor/laravel/sanctum" ] || [ ! -d "$APP_DIR/vendor/laravel/fortify" ]; then
        echo ">> Installation de Sanctum et Fortify..."
        composer require laravel/sanctum laravel/fortify \
            --no-interaction
    fi

    # 3) .env absent -> copié depuis l'exemple
    if [ ! -f "$APP_DIR/.env" ] && [ -f "$APP_DIR/.env.example" ]; then
        cp .env.example .env
    fi

    # 4) Toujours s'assurer que les dépendances sont à jour (idempotent)
    composer install --no-interaction --prefer-dist --optimize-autoloader

    if [ -z "$(grep -E '^APP_KEY=' .env 2>/dev/null | cut -d= -f2-)" ]; then
        php artisan key:generate --force
    fi

    echo ">> Attente de la base de données..."
    until nc -z db 3306; do
        sleep 2
    done

    php artisan migrate --force
    php artisan db:seed --force
    if [ ! -L "$APP_DIR/public/storage" ]; then
        php artisan storage:link
    fi

    php artisan config:cache
    php artisan route:cache

else
    echo ">> Rôle '$CONTAINER_ROLE' : attente que le conteneur 'app' termine l'installation de Laravel..."
    until [ -f "$APP_DIR/artisan" ] && [ -f "$APP_DIR/vendor/autoload.php" ]; do
        sleep 3
    done
    echo ">> Laravel détecté, démarrage du rôle '$CONTAINER_ROLE'."
fi

exec "$@"