# HOPE Health & Care — ERP V3

V3 ajoute une couche ERP inspirée de Dolibarr, adaptée au système HOPE / Kit Néné.

## Modules
- **Stock** : catalogue produits/kits, SKU, code-barres, prix achat/vente, stock minimum/maximum, emplacements, mouvements entrée/sortie/ajustement, multi-entrepôts, transferts et stock réservé.
- **Clients & fournisseurs** : fiches tiers, codes, coordonnées, ville/pays, conditions de paiement, notes et plafond client.
- **Commandes** : commande client créée par un agent autorisé, lignes produits, entrepôt, dates, statuts, validation, annulation et génération de facture.
- **Facturation** : proforma → facture, factures liées aux commandes, avoirs, paiements manuels, solde et historique.
- **Permissions** : Spatie Permission est la source de vérité. Les rôles peuvent être créés et modifiés par l'administration.

## Cycle recommandé
1. Créer le produit et définir son stock/prix.
2. Créer le client.
3. Créer la commande avec l'entrepôt de préparation.
4. Valider la commande.
5. Créer la facture ou une proforma selon le besoin.
6. Enregistrer le paiement reçu (Orange Money, virement, espèces, chèque, PayPal si utilisé).
7. Le stock est décrémenté lors de la facture validée et l'historique est conservé.

## Taxes
Les taxes sont volontairement hors du nouveau parcours ERP V3 : le calcul V3 force la taxe à 0. Les anciennes colonnes restent en base pour compatibilité et pourront être supprimées dans une migration ultérieure après validation métier.

## Routes principales
- `/api/dashboard/inventory`
- `/api/dashboard/warehouses`
- `/api/dashboard/customers`
- `/api/dashboard/suppliers`
- `/api/dashboard/orders`
- `/api/dashboard/documents`
- `/api/dashboard/payments`
- `/api/admin/users`
- `/api/admin/roles`
- `/api/admin/permissions`

## Installation Docker
```bash
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Puis frontend :
```bash
docker compose up -d --build
```

> Les clients ne créent pas directement les commandes : un utilisateur disposant de `orders.manage` les crée pour eux.
