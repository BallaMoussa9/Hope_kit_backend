# HOPE ERP V4 Complete

Cette version corrige et complète le cycle ERP : stock multi-entrepôts, commandes clients, commandes fournisseurs, livraisons, proformas/factures/avoirs, paiements, rapports, PDF/impression, emails et permissions.

## PDF / impression
Les endpoints PDF retournent un PDF multi-page valide. Le frontend utilise Axios + Bearer token puis une prévisualisation PDF avant impression/envoi.

## Stock
Le stock est mouvementé lors de la réception fournisseur et de la livraison client. Une facture liée à une commande ne décrémente plus le stock une deuxième fois.

## Email
Le code refuse volontairement l'envoi si `MAIL_MAILER=log`, afin de ne pas afficher un faux succès. Pour un vrai envoi, configurer SMTP dans `.env` (Brevo ou autre).

## Commandes
Une commande brouillon peut être modifiée. Une commande validée réserve le stock de son entrepôt. Une livraison libère la réservation et décrémente le stock.

## Installation Docker
1. Copier `.env.example` vers `.env` et renseigner MySQL/Docker.
2. `docker compose build --no-cache`
3. `docker compose up -d`
4. `docker compose exec app composer install`
5. `docker compose exec app php artisan key:generate`
6. `docker compose exec app php artisan migrate:fresh --seed` pour une base de test neuve.

Ne pas utiliser `migrate:fresh` sur une base contenant des données à conserver.
