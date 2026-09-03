# HOPE Health and Care — Backend Kit Néné (Étape 1 : Conteneurisation)

## Ce que contient cette étape

Uniquement l'infrastructure Docker. **Aucune logique métier n'est encore
écrite** — c'est volontaire, on l'ajoutera à l'étape 2 (migrations,
modèles) puis à l'étape 3 (authentification Sanctum + Fortify), etc.

Au premier démarrage, le conteneur `app` :
1. Détecte que `backend/` est vide.
2. Installe Laravel 11 automatiquement (`composer create-project`).
3. Installe Sanctum et Fortify.
4. Génère la clé d'application, lance les migrations par défaut de Laravel.

Ainsi, dès cette étape, tu as une API Laravel qui tourne réellement,
connectée à MySQL et Redis, prête à recevoir le code métier.

## Services

| Service        | Rôle                                                        | Port hôte |
|----------------|--------------------------------------------------------------|-----------|
| `webserver`    | Nginx — point d'entrée de l'API                              | 8000      |
| `app`          | PHP-FPM — exécute Laravel                                    | interne   |
| `db`           | MySQL 8.4                                                     | 3306      |
| `redis`        | Cache, sessions, files d'attente                              | interne   |
| `queue-worker` | Traite les jobs en arrière-plan (sync kits, envoi appels IVR) | interne   |
| `scheduler`    | Déclenche les tâches planifiées Laravel chaque minute         | interne   |
| `phpmyadmin`   | Interface web pour inspecter la base de données               | 8081      |

## Démarrage

```bash
# Depuis la racine du projet (là où se trouve docker-compose.yml)
docker compose up --build
```

Le tout premier démarrage prend quelques minutes (installation de Laravel
+ dépendances). Les démarrages suivants seront rapides.

Une fois lancé :
- API disponible sur **http://localhost:8000**
- phpMyAdmin sur **http://localhost:8081** (utilisateur `root`, mot de
  passe défini dans `.env`)

## Vérifier que tout fonctionne

```bash
curl http://localhost:8000/up
```

Doit retourner une réponse HTTP 200 (route de santé par défaut de
Laravel 11).

## Prochaines étapes (dans l'ordre)

1. ✅ Conteneurisation (cette étape)
2. ⬜ Migrations + modèles (Régions, Districts, CSCOM, Kits, Bénéficiaires,
   Événements de scan)
3. ⬜ Authentification (Sanctum pour mobile, Fortify pour le dashboard web,
   rôles agents/coordinateurs/direction)
4. ⬜ Endpoints Kits (création, distribution, confirmation d'utilisation,
   synchronisation offline)
5. ⬜ Endpoints Tableau de bord (KPI, filtres région/district/centre)
6. ⬜ Endpoints Bénéficiaires + IVR (planification des appels, statuts)
7. ⬜ Rapports + Alertes
8. ⬜ Dockerfile de production (build optimisé, sans les outils de dev)

## Notes importantes

- Le mot de passe MySQL par défaut dans `.env` est à changer avant toute
  mise en production.
- `backend/` est monté en volume — toute modification de code (une fois
  écrit) est visible immédiatement sans reconstruire l'image.
- Le conteneur `queue-worker` est indispensable dès qu'on ajoutera les
  jobs IVR et la synchronisation — ne pas le supprimer même s'il semble
  inactif pour l'instant.
