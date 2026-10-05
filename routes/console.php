<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Appels IVR arrivés à échéance — toutes les 5 minutes (étape 5)
Schedule::command('ivr:process-due-calls')->everyFiveMinutes();

// Détection des alertes (stock faible, distributions sans confirmation)
// — une fois par jour suffit, ce ne sont pas des situations qui changent
// à la minute près.
Schedule::command('alerts:detect')->dailyAt('06:00');
