# HOPE Backend V5 — installation

1. Copier `.env.example` vers `.env` ou reprendre ton `.env` local.
2. Générer la clé si nécessaire :

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan migrate:fresh --seed
```

3. Vérifier :

```bash
docker compose ps
curl -I http://localhost:8000
```

L'API est exposée par Nginx sur `http://localhost:8000` dans la configuration Docker fournie.

Pour l'email réel, remplacer `MAIL_MAILER=log` par une configuration SMTP, puis vider la configuration Laravel.
