<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IvrCall;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IvrWebhookController extends Controller
{
    /**
     * Endpoint appelé PAR L'OPÉRATEUR TÉLÉPHONIQUE (pas par le dashboard
     * ni le mobile) pour donner le statut final d'un appel. Protégé par
     * un jeton partagé simple (voir .env: IVR_WEBHOOK_SECRET) plutôt que
     * Sanctum, puisque l'appelant n'est pas un utilisateur de l'app.
     *
     * Le format exact du payload dépendra de l'opérateur retenu — ce
     * contrôleur attend un format générique à adapter (voir le mapping
     * dans IvrGateway le jour où un vrai fournisseur est branché).
     */
    public function update(Request $request, IvrCall $call)
    {
        $configuredSecret = (string) config('services.ivr_webhook.secret');
        $providedSecret = (string) $request->header('X-Webhook-Secret');

        if ($configuredSecret === '' || ! hash_equals($configuredSecret, $providedSecret)) {
            abort(401, 'Jeton de webhook invalide.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['answered', 'no_answer', 'failed'])],
        ]);

        $call->update(['status' => $validated['status']]);

        return response()->json(['message' => 'Statut mis à jour.']);
    }
}
