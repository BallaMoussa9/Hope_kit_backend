<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmKitUsageRequest;
use App\Http\Requests\DistributeKitRequest;
use App\Models\Kit;
use App\Services\KitEventProcessor;
use Illuminate\Http\Request;

class KitScanController extends Controller
{
    public function __construct(protected KitEventProcessor $processor) {}

    /**
     * Appelé juste après le scan de la caméra — retourne l'état actuel du
     * kit pour que l'app mobile sache quel écran afficher ensuite
     * (formulaire de distribution si le kit est en stock, écran de
     * confirmation d'utilisation s'il est déjà distribué, etc.)
     */
    public function lookup(string $qrCode)
    {
        $kit = Kit::with(['beneficiary', 'healthCenter', 'project'])
            ->where('qr_code', $qrCode)
            ->first();

        if (! $kit) {
            return response()->json([
                'message' => "Aucun kit trouvé pour ce QR Code.",
                'suggested_action' => 'manual_entry_or_report',
            ], 404);
        }

        return response()->json([
            'kit' => [
                'id' => $kit->id,
                'qr_code' => $kit->qr_code,
                'status' => $kit->status,
                'project' => $kit->project?->name,
                'health_center' => $kit->healthCenter?->name,
                'beneficiary' => $kit->beneficiary ? [
                    'id' => $kit->beneficiary->id,
                    'full_name' => $kit->beneficiary->full_name,
                    'phone' => $kit->beneficiary->phone,
                ] : null,
                'distributed_at' => $kit->distributed_at,
            ],
            // Indique à l'app quel écran ouvrir directement
            'next_step' => match ($kit->status) {
                'created', 'in_stock' => 'distribute',
                'distributed' => 'confirm_usage',
                default => 'read_only', // used / not_used : déjà clos
            },
        ]);
    }

    public function distribute(DistributeKitRequest $request, string $qrCode)
    {
        $result = $this->processor->process(
            $request->toEventPayload($qrCode),
            $request->user()
        );

        return response()->json($result, $result['status'] === 'error' ? 422 : 200);
    }

    public function confirmUsage(ConfirmKitUsageRequest $request, string $qrCode)
    {
        $result = $this->processor->process(
            $request->toEventPayload($qrCode),
            $request->user()
        );

        return response()->json($result, $result['status'] === 'error' ? 422 : 200);
    }
}
