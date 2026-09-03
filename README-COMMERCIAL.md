# HOPE Health & Care — Stock & Gestion commerciale

Cette version ajoute au backend Laravel :
- gestion des produits/kits et du stock ;
- entrées, sorties et ajustements de stock ;
- seuils de stock faible ;
- fournisseurs ;
- clients ;
- devis ;
- factures proforma ;
- factures de solde / clôture ;
- factures d'avoir ;
- conversions Devis -> Proforma -> Solde/Clôture ;
- enregistrement des paiements ;
- mouvements de stock liés aux factures finales et aux avoirs ;
- modèles imprimables avec le logo HOPE.

## Déploiement

Les nouvelles migrations sont exécutées normalement par `php artisan migrate`. Ne pas utiliser `migrate:fresh` sur une base de production.

Le produit `KIT-NENE` / `Kit Néné` est créé automatiquement avec un stock initial à 0. Le prix de vente et le seuil peuvent être modifiés depuis l'interface.
