# HOPE ERP V5 — corrections fonctionnelles

Cette version reprend le parcours commercial demandé :

- Commandes clients : création, modification des brouillons, changement de statut encadré, détails, prévisualisation PDF, téléchargement, impression, email et création d'un bon de livraison.
- Proformas / factures : workflow `Brouillon → Envoyé → Accepté`, transformation en facture uniquement après acceptation, changement de statut encadré, PDF A4 structuré, prévisualisation, téléchargement, impression et email.
- Paiements : saisie manuelle (Orange Money/Moov Money/espèces/virement/chèque/PayPal/autre), référence de transaction, statut `Enregistré / Validé / Annulé`, annulation avec recalcul du solde, reçu PDF, impression et email.
- Rapports : génération par période, vues sans IDs techniques, PDF A4, prévisualisation, téléchargement, impression et email. Un rapport n'a pas de statut métier persistant : son état est « généré » lorsqu'il a été produit à partir des filtres.
- Commandes fournisseurs : modification des brouillons, validation, réception, PDF, impression et email.
- Bons de livraison : PDF, impression, prévisualisation et email.
- PDF : générateur interne sans dépendance externe, avec mise en page A4, blocs client, adresses, tableaux, totaux, règlement et pied de page. Le style reprend l'organisation du modèle fourni sans recopier sa marque.

## Email réel

Dans `.env`, `MAIL_MAILER=log` signifie que Laravel ne fait pas d'envoi SMTP réel. Pour l'envoi réel, configurer par exemple Brevo SMTP :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=TON_EMAIL
MAIL_PASSWORD=TA_CLE_SMTP
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=TON_EMAIL
MAIL_FROM_NAME="HOPE Health and Care"
```

Puis :

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan cache:clear
```

## Installation locale

```bash
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
```
