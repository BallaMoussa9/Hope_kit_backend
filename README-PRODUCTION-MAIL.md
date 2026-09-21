# Configuration SMTP production (Render)

Dans Render > Service backend > Environment, définir les variables suivantes :

```text
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-adresse@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-d-application-Google
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=votre-adresse@gmail.com
MAIL_FROM_NAME=HOPE Health and Care
PHP_FPM_HOST=127.0.0.1
```

Ne jamais mettre le vrai mot de passe ou le vrai mot de passe d'application dans GitHub. Après modification des variables Render, redéployer le service.
