# HOPE ERP V3 — changements

Cette version transforme les fonctions commerciales en un mini-ERP inspiré de Dolibarr, sans copier son code :

- Produits / kits avec SKU, code-barres, catégories, emplacement, prix achat/vente, min/max.
- Stock global + stock par entrepôt + réserve sur commandes + transferts.
- Mouvements traçables avec utilisateur, fournisseur, référence et motif.
- Fiches clients et fournisseurs enrichies.
- Commandes clients : création par agent autorisé, validation avec réservation de stock, annulation, génération de facture.
- Facturation : proforma, facture, avoir, paiements, soldes et historique.
- Taxes retirées du nouveau calcul commercial (taux forcé à 0).
- Permissions dynamiques Spatie : les rôles et permissions sont administrables.
- Routes administration corrigées : `/api/admin/*` au lieu de `/api/mobile/admin/*`.

## Important
V3 reste compatible avec les anciennes tables et le module Kit Néné. Les colonnes fiscales historiques ne sont pas supprimées automatiquement afin de ne pas casser les anciennes données.
