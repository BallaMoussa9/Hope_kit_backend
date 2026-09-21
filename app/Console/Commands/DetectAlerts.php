<?php

namespace App\Console\Commands;

use App\Services\AlertDetectionService;
use Illuminate\Console\Command;

class DetectAlerts extends Command
{
    protected $signature = 'alerts:detect';

    protected $description = "Détecte les situations à signaler (stock faible, kits distribués trop longtemps sans confirmation).";

    public function handle(AlertDetectionService $service): int
    {
        $results = $service->runAll();

        $this->info("Stock faible : {$results['low_stock']} nouvelle(s) alerte(s).");
        $this->info("Distributions en attente : {$results['stale_distribution']} nouvelle(s) alerte(s).");

        return self::SUCCESS;
    }
}
