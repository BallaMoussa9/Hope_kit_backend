<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\HealthCenter;
use App\Models\Kit;
use Illuminate\Support\Facades\DB;

class AlertDetectionService
{
    /**
     * Lance toutes les vérifications — appelé par la commande planifiée
     * (voir app/Console/Commands/DetectAlerts.php), une fois par jour.
     */
    public function runAll(): array
    {
        return [
            'low_stock' => $this->detectLowStock(),
            'stale_distribution' => $this->detectStaleDistributions(),
        ];
    }

    protected function detectLowStock(): int
    {
        $threshold = config('alerts.low_stock_threshold');
        $created = 0;

        $stockByCenter = Kit::query()
            ->where('status', 'in_stock')
            ->whereNotNull('current_health_center_id')
            ->select('current_health_center_id', DB::raw('count(*) as total'))
            ->groupBy('current_health_center_id')
            ->pluck('total', 'current_health_center_id');

        foreach (HealthCenter::where('is_active', true)->get() as $center) {
            $stock = $stockByCenter[$center->id] ?? 0;

            if ($stock < $threshold) {
                $created += $this->createIfNotExists([
                    'type' => 'low_stock',
                    'severity' => $stock === 0 ? 'critical' : 'warning',
                    'health_center_id' => $center->id,
                    'kit_id' => null,
                    'message' => "Stock faible - {$center->name} : {$stock} kit(s) en stock (seuil : {$threshold}).",
                    'status' => 'open',
                    'detected_at' => now(),
                ]);
            }
        }

        return $created;
    }

    protected function detectStaleDistributions(): int
    {
        $days = config('alerts.stale_distribution_days');
        $created = 0;

        $staleKits = Kit::where('status', 'distributed')
            ->where('distributed_at', '<=', now()->subDays($days))
            ->get();

        foreach ($staleKits as $kit) {
            $daysAgo = (int) $kit->distributed_at->diffInDays(now());

            $created += $this->createIfNotExists([
                'type' => 'stale_distribution',
                'severity' => $daysAgo > $days * 1.5 ? 'critical' : 'warning',
                'health_center_id' => $kit->current_health_center_id,
                'kit_id' => $kit->id,
                'message' => "Kit distribué depuis {$daysAgo} jours sans confirmation d'utilisation - {$kit->healthCenter?->name}.",
                'status' => 'open',
                'detected_at' => now(),
            ]);
        }

        return $created;
    }

    /**
     * Évite de recréer une alerte identique déjà ouverte (grâce à la
     * contrainte unique sur la table) — retourne 1 si créée, 0 sinon.
     */
    protected function createIfNotExists(array $data): int
    {
        $exists = Alert::where('type', $data['type'])
            ->where('health_center_id', $data['health_center_id'])
            ->where('kit_id', $data['kit_id'])
            ->where('status', 'open')
            ->exists();

        if ($exists) {
            return 0;
        }

        Alert::create($data);

        return 1;
    }
}
