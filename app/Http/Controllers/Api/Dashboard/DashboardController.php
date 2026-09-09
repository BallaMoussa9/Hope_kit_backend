<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\HealthCenter;
use App\Models\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Écran "Aperçu Global" — les 4 KPI en haut + le classement par
     * district. Filtrable par projet/région/district/centre/période.
     */
    public function kpi(Request $request)
    {
        $kits = $this->filteredKits($request);

        $total = (clone $kits)->count();
        $inStock = (clone $kits)->where('status', 'in_stock')->count();
        $distributed = (clone $kits)->whereIn('status', ['distributed', 'used', 'not_used'])->count();
        $used = (clone $kits)->where('status', 'used')->count();

        $tauxUtilisation = $distributed > 0 ? round(($used / $distributed) * 100, 1) : 0;

        return response()->json([
            'total_kits' => $total,
            'kits_en_stock' => $inStock,
            'kits_distribues' => $distributed,
            'kits_utilises' => $used,
            'taux_utilisation' => $tauxUtilisation,
        ]);
    }

    /**
     * Répartition géographique — alimente la carte + le classement par
     * région/district/centre (écran "Régions" et le graphique de l'écran
     * "Aperçu Global").
     */
    public function byRegion(Request $request)
    {
        $kits = $this->filteredKits($request);

        $rows = (clone $kits)
            ->join('health_centers', 'kits.current_health_center_id', '=', 'health_centers.id')
            ->join('districts', 'health_centers.district_id', '=', 'districts.id')
            ->join('regions', 'districts.region_id', '=', 'regions.id')
            ->select(
                'regions.id as region_id',
                'regions.name as region_name',
                'districts.id as district_id',
                'districts.name as district_name',
                DB::raw('COUNT(*) as total_kits'),
                DB::raw("SUM(CASE WHEN kits.status = 'used' THEN 1 ELSE 0 END) as used_kits"),
                DB::raw("SUM(CASE WHEN kits.status IN ('distributed','used','not_used') THEN 1 ELSE 0 END) as distributed_kits")
            )
            ->groupBy('regions.id', 'regions.name', 'districts.id', 'districts.name')
            ->get()
            ->map(function ($row) {
                $row->taux_utilisation = $row->distributed_kits > 0
                    ? round(($row->used_kits / $row->distributed_kits) * 100, 1)
                    : 0;

                return $row;
            });

        return response()->json($rows);
    }

    /**
     * Classement des centres de santé par rapidité d'utilisation — répond
     * directement à : "quels centres utilisent rapidement leurs kits et
     * lesquels en utilisent peu ?"
     */
    public function healthCenterRanking(Request $request)
    {
        $healthCenters = HealthCenter::query()
            ->withCount([
                'kits as total_kits',
                'kits as used_kits' => fn ($q) => $q->where('status', 'used'),
                'kits as distributed_kits' => fn ($q) => $q->whereIn('status', ['distributed', 'used', 'not_used']),
            ])
            ->get()
            ->map(function ($hc) {
                $hc->taux_utilisation = $hc->distributed_kits > 0
                    ? round(($hc->used_kits / $hc->distributed_kits) * 100, 1)
                    : 0;

                // Délai moyen (en jours) entre distribution et utilisation
                $hc->delai_moyen_jours = Kit::where('current_health_center_id', $hc->id)
                    ->where('status', 'used')
                    ->whereNotNull('distributed_at')
                    ->whereNotNull('used_at')
                    ->get()
                    ->avg(fn ($k) => $k->distributed_at->diffInDays($k->used_at));

                return $hc;
            })
            ->sortByDesc('taux_utilisation')
            ->values();

        return response()->json($healthCenters);
    }

    protected function filteredKits(Request $request)
    {
        return Kit::query()
            ->when($request->query('project_id'), fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->query('health_center_id'), fn ($q, $v) => $q->where('current_health_center_id', $v))
            ->when($request->query('district_id'), function ($q, $v) {
                $q->whereHas('healthCenter', fn ($hc) => $hc->where('district_id', $v));
            })
            ->when($request->query('region_id'), function ($q, $v) {
                $q->whereHas('healthCenter.district', fn ($d) => $d->where('region_id', $v));
            })
            ->when($request->query('date_from'), fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->query('date_to'), fn ($q, $v) => $q->where('created_at', '<=', $v));
    }
}
