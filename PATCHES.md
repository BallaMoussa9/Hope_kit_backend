# Patches à appliquer à la main

## 1) `backend/bootstrap/providers.php`

Remplace tout le contenu par :

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
];
```

(garde `AppServiceProvider` s'il y en avait déjà un — ajoute juste la
ligne `FortifyServiceProvider::class`)

## 2) `backend/bootstrap/app.php`

Remplace tout le contenu par :

```php
<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Sanctum : permet l'authentification par session/cookie pour le
        // dashboard Vue (SPA) en plus de l'auth par token pour le mobile.
        $middleware->statefulApi();

        // Alias utilisé dans routes/api.php : ->middleware('role:direction')
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

**Attention** : si ton `bootstrap/app.php` généré par Laravel 11 a déjà du
contenu personnalisé (peu probable à ce stade), regarde bien qu'on ajoute
seulement `$middleware->statefulApi();` et le bloc `alias([...])` dans
`withMiddleware()`, sans supprimer le reste.

## Pourquoi `statefulApi()` ?

C'est ce qui permet à Sanctum de reconnaître les requêtes venant de ton
dashboard Vue (même origine déclarée dans `SANCTUM_STATEFUL_DOMAINS`)
comme des requêtes de session classique (cookie), **et** en même temps de
laisser les requêtes de l'app mobile (avec un header `Authorization:
Bearer ...`) passer en authentification par token. Les deux mécanismes
cohabitent sur le même guard `sanctum`.
