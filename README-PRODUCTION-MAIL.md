# HOPE — Envoi email en production

## Architecture

Les emails HOPE passent par la file Laravel **database**. Le serveur HTTP ne bloque donc plus en attendant Gmail.

Flux :

```text
Action HOPE
   ↓
Mail::to(...)->queue(...)
   ↓
MySQL / table jobs
   ↓
php artisan queue:work database
   ↓
SMTP Gmail
   ↓
Destinataire
```

## Variables Render

```env
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=mysql
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=120
CACHE_STORE=file
SESSION_DRIVER=file
RUN_QUEUE_WORKER=true
RUN_SCHEDULER=true

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=notifications@hopehealthandcare.org
MAIL_PASSWORD=VOTRE_NOUVEAU_MOT_DE_PASSE_APPLICATION
MAIL_FROM_ADDRESS=notifications@hopehealthandcare.org
MAIL_FROM_NAME="HOPE Health and Care"
```

Ne jamais mettre le mot de passe d'application Gmail dans GitHub.

## Après déploiement

Vérifier les migrations :

```bash
php artisan migrate --force
```

Vérifier les jobs :

```bash
php artisan queue:failed
```

Le worker doit tourner en permanence :

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=120 --queue=default
```

## Emails couverts

- Commande client
- Commande fournisseur
- Bon de livraison
- Facture / document commercial
- Reçu de paiement
- Rapport PDF
- Email de bienvenue lors de la création d'un compte

Les pièces PDF sont générées par le worker au moment de l'envoi.
