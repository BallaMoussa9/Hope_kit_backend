<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'views' => false,
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],
    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],
    'lockout' => [
        'enabled' => true,
    ],
    'redirects' => [
        'login' => '/login',
    ],
    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::updatePasswords(),
    ],
];
