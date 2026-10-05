# HOPE ERP — intégration paiements, QR et audit (base de travail)

Cette livraison est une intégration incrémentale sur les sources jointes. Elle n'est pas certifiée « 100 % production » : l'environnement de travail ne contient pas `vendor/`, `composer` n'est pas disponible, et `npm run build` échoue car `vite` n'est pas installé dans les dépendances locales.

## Ajouts inclus
- Migration d'extension pour le mode de paiement des commandes (`before_delivery`, `after_delivery`, `mixed`), le montant minimal exigible avant livraison, les échéances et la devise configurable.
- Réglage administrateur de devise (`XOF`, `EUR`, `USD`, etc.) via `GET /api/admin/currency` et `PUT /api/admin/currency`; lecture pour les utilisateurs du tableau de bord via `GET /api/dashboard/currency`. La devise de chaque document existant reste conservée.
- Contrôle serveur du seuil exigible avant la livraison : paiement intégral pour `before_delivery`, seuil configuré pour `mixed`, aucun seuil préalable pour `after_delivery`.
- Génération des factures depuis une commande sous verrou de transaction pour réduire le risque de doublon.
- Audit additionnel des écritures API authentifiées et des consultations de modules opérationnels ciblés; filtres et pagination dans le journal d'administration.
- Étiquettes QR individuelles et impression HTML par kit ou par lot. Le QR contient uniquement l'identifiant opaque du kit, sans donnée personnelle. La génération utilise `bacon/bacon-qr-code`, présent dans le fichier lock fourni; vérifier sa disponibilité après `composer install`.
- Écrans Vue pour le journal d'audit et la devise; formulaire de commande avec mode de paiement et échéance; boutons d'impression des QR.

## Mise en place locale
1. Sauvegarder la base et les fichiers.
2. Installer les dépendances dans le backend (`composer install`) puis vérifier la version réellement résolue. Le backend fourni est verrouillé sur Laravel 11.55.1; Laravel 13 n'a pas été basculé dans cette livraison, car aucun Composer/résolveur de dépendances ni environnement de tests Laravel n'était disponible ici. Ne pas modifier uniquement le numéro de version sans mise à niveau et résolution complète du lock.
3. Installer le frontend avec `npm ci`, puis exécuter `npm run build`.
4. Vérifier `.env` et `QUEUE_CONNECTION=database` avec migrations `jobs`/`failed_jobs`; exécuter `php artisan migrate --force` après sauvegarde.
5. Démarrer un worker séparé (`php artisan queue:work database --sleep=3 --tries=3 --timeout=120`) en production; superviser les jobs échoués.
6. Tester en préproduction les trois modes, les seuils de livraison, les paiements partiels, les annulations, les permissions, les devises, les QR et la lecture d'audit avant déploiement.

## Limites connues à finaliser avant production
- Audit générique enregistre la requête et son résultat HTTP, pas une comparaison universelle exacte des valeurs avant/après pour toutes les tables. Les opérations SQL externes, tâches non HTTP et intégrations tierces nécessitent des événements explicites supplémentaires. Les journaux ne sont pas inviolables à un administrateur DB; exporter vers un stockage append-only/WORM ou SIEM pour une exigence forte.
- Les reçus PDF, remboursements comptables, échéancier avec échéances multiples distinctes et statut de livraison avec signature/photo horodatée doivent être validés et, selon le workflow métier, complétés. Les paiements enregistrés restent une saisie manuelle et ne représentent pas une confirmation directe Orange Money/Moov Money.
- Les vues de synthèse, les rapports, les migrations des anciennes commandes, et les tests automatisés end-to-end doivent être complétés dans l'environnement avec DB réelle.
- La migration Laravel 13 exige une matrice de compatibilité des dépendances, un lock Composer régénéré et une suite de tests; elle n'est pas incluse/validée ici.
- Aucune promesse de 100 millions de connexions simultanées : capacité à établir par tests progressifs, métriques, budgets et infrastructure distribuée réelle.
