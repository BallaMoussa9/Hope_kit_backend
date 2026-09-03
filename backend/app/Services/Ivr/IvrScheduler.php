<?php

namespace App\Services\Ivr;

use App\Models\Beneficiary;
use App\Models\IvrCall;
use Carbon\Carbon;

class IvrScheduler
{
    /**
     * Calcule et enregistre tout le calendrier d'appels d'une
     * bénéficiaire à partir de sa Date Prévue d'Accouchement (DPA).
     * Appelé automatiquement à l'enregistrement (voir BeneficiaryController)
     * et peut être rappelé si la DPA est corrigée plus tard.
     */
    public function scheduleForBeneficiary(Beneficiary $beneficiary): int
    {
        if (! $beneficiary->expected_delivery_date || ! $beneficiary->ivr_consent) {
            return 0;
        }

        // On repart de zéro si le calendrier existe déjà (ex: DPA corrigée)
        $beneficiary->ivrCalls()->where('status', 'pending')->delete();

        $dpa = Carbon::parse($beneficiary->expected_delivery_date);
        $created = 0;

        foreach (config('ivr.cpn_reminder_days_before_dpa', []) as $daysBefore) {
            $scheduledAt = $dpa->copy()->subDays($daysBefore)->setTime(9, 0);

            if ($scheduledAt->isFuture()) {
                IvrCall::create([
                    'beneficiary_id' => $beneficiary->id,
                    'call_type' => 'cpn_reminder',
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]);
                $created++;
            }
        }

        foreach (config('ivr.delivery_reminder_days_before_dpa', []) as $daysBefore) {
            $scheduledAt = $dpa->copy()->subDays($daysBefore)->setTime(9, 0);

            if ($scheduledAt->isFuture()) {
                IvrCall::create([
                    'beneficiary_id' => $beneficiary->id,
                    'call_type' => 'delivery_reminder',
                    'scheduled_at' => $scheduledAt,
                    'status' => 'pending',
                ]);
                $created++;
            }
        }

        return $created;
    }
}
