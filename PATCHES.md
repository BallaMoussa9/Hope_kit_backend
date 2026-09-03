# Correctifs intégrés

Les correctifs précédemment listés ici ont été intégrés dans cette version du projet.

## Authentification SPA Vue + Sanctum

- `bootstrap/app.php` active `statefulApi()` pour l'authentification par cookie/session du dashboard Vue.
- L'alias middleware `role` est enregistré pour les routes protégées.
- Les rôles frontend utilisent désormais les mêmes valeurs que le backend (`coordinateur_projet`, `agent_sante`, `agent_logistique`).

## Docker / environnement

- Le conteneur Laravel utilise MySQL et Redis des services Docker au lieu de retomber sur SQLite/localhost.
- La queue Docker utilise Redis, cohérente avec le worker.
- Les conteneurs `app`, `queue-worker` et `scheduler` reçoivent la même configuration DB/Redis.
- MySQL et Redis disposent de contrôles de disponibilité avant le démarrage de l'application.
- `APP_KEY` n'est générée que lorsqu'elle est absente, afin de ne pas invalider les sessions à chaque redémarrage.
- Les migrations ne masquent plus leurs erreurs de démarrage.

## IVR et traçabilité

- Le secret du webhook IVR est réellement vérifié et doit être configuré.
- Une bénéficiaire créée automatiquement lors d'une distribution reçoit aussi son calendrier IVR lorsque le consentement et la DPA le permettent.
- Un kit marqué `not_used` ne reçoit plus une fausse date `used_at`.
- Un kit déjà clôturé ne peut pas être réouvert par un événement `received`.

## Validation effectuée

- Syntaxe PHP : tous les fichiers applicatifs contrôlés.
- Syntaxe JavaScript des scripts Vue : contrôlée.
- Imports relatifs frontend : contrôlés.
- Fichiers Docker Compose : YAML validé.
- Cohérence `package.json` / `package-lock.json` : validée.

Le build complet nécessite Docker/Composer pour Laravel et l'installation des dépendances npm pour Vite. Ces exécutables ne sont pas disponibles dans l'environnement de contrôle utilisé pour cette livraison.
