<?php

namespace App\Services\Ivr;

use App\Models\IvrCall;

/**
 * Contrat commun à tous les fournisseurs d'appels automatiques. Pour
 * brancher un vrai opérateur (Twilio, Africa's Talking, Orange Mali...),
 * il suffit de créer une nouvelle classe qui implémente cette interface
 * et de changer IVR_DRIVER dans .env — aucun autre fichier à modifier.
 */
interface IvrGateway
{
    /**
     * Déclenche l'appel. Doit retourner un statut immédiat
     * ('sent' si l'appel a été correctement transmis à l'opérateur,
     * 'failed' sinon) — le statut final (answered/no_answer) arrive
     * en général plus tard via un webhook (voir IvrWebhookController).
     */
    public function placeCall(IvrCall $call): string;
}
