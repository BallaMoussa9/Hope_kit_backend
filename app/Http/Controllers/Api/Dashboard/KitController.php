<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Models\HealthCenter;
use App\Models\Kit;
use App\Models\KitEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class KitController extends Controller
{
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
            ->when($request->query('search'), fn ($q, $v) => $q->where('qr_code', 'like', "%{$v}%"))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($kits);
    }

    public function show(Kit $kit)
    {
        return response()->json($kit->load(['healthCenter.district.region', 'beneficiary', 'project', 'events.user']));
    }

    public function healthCenters()
    {
        return response()->json(
            HealthCenter::with('district.region')->where('is_active', true)->orderBy('name')->get()
        );
    }

    public function beneficiaries(Request $request)
    {
        $q = trim((string) $request->query('search', ''));
        return response()->json(
            Beneficiary::with('healthCenter')
                ->when($q, fn ($query) => $query->where(function ($x) use ($q) {
                    $x->where('first_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                }))
                ->latest()
                ->limit(200)
                ->get(['id','first_name','last_name','phone','health_center_id','expected_delivery_date'])
        );
    }

    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'batch_number' => ['required', 'string', 'max:100'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'health_center_id' => ['nullable', 'exists:health_centers,id'],
            'beneficiary_id' => ['nullable', 'exists:beneficiaries,id'],
            'status' => ['required', 'in:created,in_stock,distributed,used,not_used'],
            'received_at' => ['nullable', 'date'],
            'distributed_at' => ['nullable', 'date'],
            'used_at' => ['nullable', 'date'],
            'expected_delay_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $status = $validated['status'];
        if (in_array($status, ['in_stock', 'distributed', 'used', 'not_used'], true) && empty($validated['health_center_id'])) {
            abort(422, 'Le centre de santé est obligatoire pour ce statut.');
        }
        if (in_array($status, ['distributed', 'used', 'not_used'], true) && empty($validated['beneficiary_id'])) {
            abort(422, 'La bénéficiaire est obligatoire pour ce statut.');
        }
        if (in_array($status, ['in_stock', 'distributed', 'used', 'not_used'], true) && empty($validated['received_at'])) {
            abort(422, 'La date de réception est obligatoire pour ce statut.');
        }
        if (in_array($status, ['distributed', 'used', 'not_used'], true) && empty($validated['distributed_at'])) {
            abort(422, 'La date de distribution est obligatoire pour ce statut.');
        }
        if ($status === 'used' && empty($validated['used_at'])) {
            abort(422, 'La date d’utilisation est obligatoire pour un kit utilisé.');
        }

        return DB::transaction(function () use ($validated, $status, $request) {
            $prefix = 'KN-' . now()->format('ymd') . '-';
            $kits = [];
            $events = [];

            for ($i = 0; $i < $validated['quantity']; $i++) {
                $qr = $prefix . Str::upper(Str::random(8));
                $kits[] = [
                    'qr_code' => $qr,
                    'batch_number' => $validated['batch_number'],
                    'project_id' => $validated['project_id'] ?? null,
                    'status' => $status,
                    'current_health_center_id' => $validated['health_center_id'] ?? null,
                    'beneficiary_id' => $validated['beneficiary_id'] ?? null,
                    'received_at' => $validated['received_at'] ?? null,
                    'distributed_at' => $validated['distributed_at'] ?? null,
                    'used_at' => $validated['used_at'] ?? null,
                    'expected_delay_days' => $validated['expected_delay_days'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($status !== 'created') {
                    $events[] = [
                        'client_uuid' => (string) Str::uuid(), 'kit_qr' => $qr,
                        'type' => 'received', 'occurred_at' => $validated['received_at'],
                    ];
                }
                if (in_array($status, ['distributed', 'used', 'not_used'], true)) {
                    $events[] = [
                        'client_uuid' => (string) Str::uuid(), 'kit_qr' => $qr,
                        'type' => 'distributed', 'occurred_at' => $validated['distributed_at'],
                    ];
                }
                if (in_array($status, ['used', 'not_used'], true)) {
                    $events[] = [
                        'client_uuid' => (string) Str::uuid(), 'kit_qr' => $qr,
                        'type' => $status, 'occurred_at' => $validated['used_at'] ?? $validated['distributed_at'],
                    ];
                }
            }

            Kit::insert($kits);
            $ids = Kit::whereIn('qr_code', array_column($kits, 'qr_code'))->pluck('id', 'qr_code');
            foreach ($events as $event) {
                KitEvent::create([
                    'client_uuid' => $event['client_uuid'],
                    'kit_id' => $ids[$event['kit_qr']],
                    'user_id' => $request->user()->id,
                    'health_center_id' => $validated['health_center_id'] ?? null,
                    'beneficiary_id' => $validated['beneficiary_id'] ?? null,
                    'event_type' => $event['type'],
                    'payload' => ['source' => 'dashboard_batch_registration'],
                    'occurred_at' => $event['occurred_at'],
                    'synced_at' => now(),
                ]);
            }

            return response()->json([
                'message' => count($kits) . ' kits enregistrés avec succès.',
                'qr_codes' => array_column($kits, 'qr_code'),
                'status' => $status,
                'batch_number' => $validated['batch_number'],
            ], 201);
        });
    }
}
