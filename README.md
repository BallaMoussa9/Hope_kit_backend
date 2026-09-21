# Étape 6 — Rapports & Alertes

## Installer

```bash
cp step6-reports-alerts/database/migrations/*.php backend/database/migrations/
cp step6-reports-alerts/app/Models/*.php backend/app/Models/
cp step6-reports-alerts/config/alerts.php backend/config/alerts.php
cp step6-reports-alerts/app/Services/*.php backend/app/Services/
cp step6-reports-alerts/app/Console/Commands/*.php backend/app/Console/Commands/
cp step6-reports-alerts/app/Http/Controllers/Api/Dashboard/*.php backend/app/Http/Controllers/Api/Dashboard/
cp step6-reports-alerts/routes/api.php backend/routes/api.php
cp step6-reports-alerts/routes/console.php backend/routes/console.php
```

## Appliquer

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link   # déjà fait normalement, sans risque de le refaire
docker compose exec app php artisan config:clear
```

## Tester les rapports

```bash
# Générer un rapport de performance par centre
curl -X POST http://localhost:8000/api/dashboard/reports \
  -H "Authorization: Bearer TOKEN_DIRECTION" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"type":"performance_par_centre"}'
```
→ la réponse contient `download_url` — ouvre-le dans le navigateur ou
télécharge-le, ça doit être un `.csv` lisible dans Excel (avec les
accents corrects).

```bash
# Lister l'historique des rapports (alimente l'écran "Rapports")
curl http://localhost:8000/api/dashboard/reports \
  -H "Authorization: Bearer TOKEN_DIRECTION" -H "Accept: application/json"
```

## Tester les alertes

```bash
# Forcer la détection immédiatement (au lieu d'attendre 6h du matin)
docker compose exec app php artisan alerts:detect

# Voir les alertes ouvertes
curl http://localhost:8000/api/dashboard/alerts \
  -H "Authorization: Bearer TOKEN_DIRECTION" -H "Accept: application/json"
```

Comme tu n'as sans doute pas encore 60 jours de données ni de centre à 0
kit en stock, il est normal de voir `low_stock` alerts apparaître dès
maintenant si tu as peu de kits en stock (`ALERT_LOW_STOCK_THRESHOLD=5`
par défaut) — ajuste le seuil dans `.env` si besoin :

```env
ALERT_LOW_STOCK_THRESHOLD=5
ALERT_STALE_DISTRIBUTION_DAYS=60
```

## Note sur le format des rapports

Pour l'instant, seul le **CSV** est disponible (compatible Excel/Google
Sheets, sans dépendance externe donc zéro risque de retomber sur le
blocage Composer qu'on a eu). Si tu veux du **PDF** plus tard, on ajoute
`barryvdh/laravel-dompdf` à ce moment-là — c'est un ajout isolé qui ne
touche à rien d'existant.

## Ce qu'il te reste, au global

Avec cette étape, **toutes les briques fonctionnelles du cahier des
charges sont posées côté backend** :

- ✅ Traçabilité QR Code (créé → stock → distribué → utilisé), hors-ligne
- ✅ Tableau de bord (KPI, répartition géo, classement centres)
- ✅ Appels automatiques IVR (planification + envoi + webhook)
- ✅ Rapports exportables
- ✅ Alertes automatiques

**Ce qui reste, si tu veux aller plus loin :**
- Gestion des utilisateurs par la direction (créer/désactiver des
  comptes agents/coordinateurs depuis le dashboard — actuellement fait
  uniquement via le seeder ou `php artisan tinker`)
- Un vrai fournisseur IVR branché (Twilio/Africa's Talking) à la place
  du `LogIvrGateway`
- Tests automatisés (PHPUnit/Pest) — je peux les générer si tu veux
- Dockerfile de production (optimisé, sans les outils de dev)
- Connexion réelle du frontend Ionic/Vue (mobile) et Vue (dashboard) à
  cette API — c'est l'étape suivante logique une fois que tu as les
  maquettes Stitch finalisées

Dis-moi laquelle de ces suites t'intéresse en premier.
