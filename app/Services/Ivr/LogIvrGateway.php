<?php

namespace App\Services\Ivr;

use App\Models\IvrCall;
use Illuminate\Support\Facades\Log;

/**
 * Implémentation de développement : n'appelle personne, écrit simplement
 * dans les logs. Permet de tester tout le pipeline de planification et
 * de traitement des appels sans dépendre d'un compte opérateur payant.
 */
class LogIvrGateway implements IvrGateway
{
    public function placeCall(IvrCall $call): string
    {
        $beneficiary = $call->beneficiary;
        $message = config("ivr.messages.{$call->call_type}.{$beneficiary->preferred_language}")
            ?? config("ivr.messages.{$call->call_type}.francais");

        Log::info('[IVR][SIMULATION] Appel déclenché', [
            'call_id' => $call->id,
            'beneficiary' => $beneficiary->full_name,
            'phone' => $beneficiary->phone,
            'language' => $beneficiary->preferred_language,
            'message' => $message,
        ]);

        return 'sent';
    }
}
