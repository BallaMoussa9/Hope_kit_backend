# HOPE ERP/CRM V2 — extension livrée

Cette version étend le projet HOPE existant avec une base de gestion inspirée de Dolibarr.

## Fonctionnalités ajoutées
- RBAC dynamique : rôles et permissions gérés par l'administrateur.
- Un employé sans rôle métier n'accède pas aux modules.
- Gestion multi-entrepôts et stocks par entrepôt.
- Transferts de stock.
- Historique/audit des opérations.
- Paiements séparés de la facture, avec paiements partiels ou intégral.
- Paiement externe (Orange Money, Moov Money, espèces, virement, PayPal comme mode enregistré) puis saisie manuelle par un agent autorisé.
- Confirmation automatique du paiement par email au client lorsque son email existe.
- Email automatique lors de la création d'un compte.
- Notifications internes dans l'application.
- Envoi manuel d'une facture par email avec lien de consultation.
- Conservations des modules Kit Néné, QR/offline, IVR et dashboard existants.

## Installation locale Docker
1. Copier `.env.example` vers `.env` et renseigner la base de données/mail.
2. `docker compose up -d --build`
3. Dans le conteneur PHP : `php artisan migrate`
4. `php artisan db:seed`
5. Vérifier `docker compose ps` et les logs.

Le frontend utilise `VITE_API_URL` et reste compatible avec le déploiement Docker existant.

## Important
Le ZIP ne contient pas les dépendances générées (`vendor`/`node_modules`). Sur une machine disposant d'un accès réseau, exécuter `composer install` côté backend et `npm ci` côté frontend avant le build.
