# HOPE V6 Backend — intégration réelle des vues

Le backend est celui fourni par l'utilisateur. Aucun workflow ERP existant n'est remplacé.

Modifications V6 :
- PUT /api/admin/users/{user} pour l'édition réelle d'un utilisateur.
- docker-compose : l'image hope-kit-app est construite une seule fois par le service app ; queue_worker et scheduler réutilisent cette image.
- Les migrations et l'authentification existantes sont conservées.
