<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncKitEventsRequest;
use App\Services\KitEventProcessor;

class SyncController extends Controller
{
    public function __construct(protected KitEventProcessor $processor) {}

    /**
     * Le téléphone envoie ici, en un seul appel, tous les scans réalisés
     * pendant qu'il était hors-ligne. Chaque événement est traité
     * indépendamment (un échec sur l'un n'annule pas les autres), et le
     * résultat détaillé par événement est renvoyé pour que l'app puisse
     * marquer localement chaque scan comme "synchronisé" ou "en erreur".
     */
    public function push(SyncKitEventsRequest $request)
    {
        $user = $request->user();
        $results = [];

        foreach ($request->validated()['events'] as $event) {
            try {
                $results[] = array_merge(
                    ['client_uuid' => $event['client_uuid']],
                    $this->processor->process($event, $user)
                );
            } catch (\Throwable $e) {
                $results[] = [
                    'client_uuid' => $event['client_uuid'],
                    'status' => 'error',
                    'kit_id' => null,
                    'message' => 'Erreur serveur : ' . $e->getMessage(),
                ];
            }
        }

        $summary = [
            'total' => count($results),
            'ok' => count(array_filter($results, fn ($r) => $r['status'] === 'ok')),
            'duplicate' => count(array_filter($results, fn ($r) => $r['status'] === 'duplicate')),
            'error' => count(array_filter($results, fn ($r) => $r['status'] === 'error')),
        ];

        return response()->json([
            'summary' => $summary,
            'results' => $results,
        ]);
    }
}
