<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Kit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KitController extends Controller
{
    /**
     * Alimente l'écran "Gestion des Kits" : tableau paginé, filtrable par
     * statut / région / district / centre / projet / période.
     */
    public function index(Request $request)
    {
        $kits = Kit::query()
            ->with(['healthCenter.district.region', 'beneficiary', 'project'])
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('project_id'), fn ($q, $v) => $q->where('project_id', $v))
            ->when($request->query('health_center_id'), fn ($q, $v) => $q->where('current_health_center_id', $v))
            ->when($request->query('district_id'), function ($q, $v) {
                $q->whereHas('healthCenter', fn ($hc) => $hc->where('district_id', $v));
            })
            ->when($request->query('region_id'), function ($q, $v) {
                $q->whereHas('healthCenter.district', fn ($d) => $d->where('region_id', $v));
            })
            ->when($request->query('search'), function ($q, $v) {
                $q->where('qr_code', 'like', "%{$v}%");
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($kits);
    }

    public function show(Kit $kit)
    {
        $kit->load(['healthCenter.district.region', 'beneficiary', 'project', 'events.user']);

        return response()->json($kit);
    }

    /**
     * Génère un nouveau lot de kits avec des QR Codes uniques — utilisé
     * par la direction/coordinateur à la réception d'une nouvelle
     * production de Kits Néné.
     */
    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'batch_number' => ['required', 'string', 'max:100'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $prefix = 'KN-' . now()->format('ymd') . '-';
        $kits = [];

        for ($i = 0; $i < $validated['quantity']; $i++) {
            $kits[] = [
                'qr_code' => $prefix . Str::upper(Str::random(8)),
                'batch_number' => $validated['batch_number'],
                'project_id' => $validated['project_id'] ?? null,
                'status' => 'created',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Kit::insert($kits);

        return response()->json([
            'message' => count($kits) . ' kits créés avec succès.',
            'qr_codes' => array_column($kits, 'qr_code'),
        ], 201);
    }
}
