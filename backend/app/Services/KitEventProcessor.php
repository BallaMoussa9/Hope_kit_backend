<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Kit;
use App\Models\KitEvent;
use App\Models\User;
use App\Services\Ivr\IvrScheduler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Point d'entrée UNIQUE pour appliquer un événement de scan à un kit.
 *
 * Utilisé à la fois par les actions "en direct" du mobile (scan avec
 * connexion) et par la synchronisation en lot (scans faits hors-ligne,
 * envoyés d'un coup au retour du réseau) — ça garantit que les deux
 * chemins produisent exactement le même résultat, et que l'idempotence
 * (basée sur client_uuid) est respectée partout.
 */
class KitEventProcessor
{
    public function __construct(protected IvrScheduler $ivrScheduler) {}

    /**
     * @param array{
     *   client_uuid: string,
     *   qr_code: string,
     *   event_type: string,
     *   payload?: array,
     *   occurred_at: string,
     *   beneficiary?: array,
     * } $data
     * @return array{status: string, kit_id: int|null, message: string}
     */
    public function process(array $data, ?User $user): array
    {
        // Idempotence : si ce client_uuid a déjà été traité (ex : le
        // téléphone renvoie le même scan après une coupure réseau au
        // milieu de la synchronisation précédente), on ne rejoue rien.
        $existing = KitEvent::where('client_uuid', $data['client_uuid'])->first();
        if ($existing) {
            return [
                'status' => 'duplicate',
                'kit_id' => $existing->kit_id,
                'message' => 'Événement déjà synchronisé, ignoré.',
            ];
        }

        $kit = Kit::where('qr_code', $data['qr_code'])->first();
        if (! $kit) {
            return [
                'status' => 'error',
                'kit_id' => null,
                'message' => "Aucun kit trouvé pour le QR Code {$data['qr_code']}.",
            ];
        }

        return match ($data['event_type']) {
            'received' => $this->applyReceived($kit, $data, $user),
            'distributed' => $this->applyDistributed($kit, $data, $user),
            'used' => $this->applyUsage($kit, $data, $user, used: true),
            'not_used' => $this->applyUsage($kit, $data, $user, used: false),
            default => throw new InvalidArgumentException("event_type inconnu : {$data['event_type']}"),
        };
    }

    protected function applyReceived(Kit $kit, array $data, ?User $user): array
    {
        if (! in_array($kit->status, ['created', 'in_stock'], true)) {
            return [
                'status' => 'error',
                'kit_id' => $kit->id,
                'message' => "Ce kit a le statut '{$kit->status}' et ne peut plus être enregistré comme réceptionné.",
            ];
        }

        return DB::transaction(function () use ($kit, $data, $user) {
            $healthCenterId = $data['payload']['health_center_id'] ?? $user?->health_center_id;

            $kit->update([
                'status' => 'in_stock',
                'current_health_center_id' => $healthCenterId,
                'received_at' => $data['occurred_at'],
            ]);

            $this->recordEvent($kit, 'received', $data, $user, $healthCenterId, null);

            return ['status' => 'ok', 'kit_id' => $kit->id, 'message' => 'Kit marqué en stock.'];
        });
    }

    protected function applyDistributed(Kit $kit, array $data, ?User $user): array
    {
        if (! in_array($kit->status, ['created', 'in_stock'], true)) {
            return [
                'status' => 'error',
                'kit_id' => $kit->id,
                'message' => "Ce kit a le statut '{$kit->status}' et ne peut pas être redistribué.",
            ];
        }

        return DB::transaction(function () use ($kit, $data, $user) {
            $beneficiary = $this->resolveBeneficiary($data, $user);
            $healthCenterId = $data['payload']['health_center_id'] ?? $user?->health_center_id;

            $kit->update([
                'status' => 'distributed',
                'beneficiary_id' => $beneficiary->id,
                'current_health_center_id' => $healthCenterId,
                'distributed_at' => $data['occurred_at'],
            ]);

            $this->recordEvent($kit, 'distributed', $data, $user, $healthCenterId, $beneficiary->id);

            return ['status' => 'ok', 'kit_id' => $kit->id, 'message' => 'Kit marqué distribué.'];
        });
    }

    protected function applyUsage(Kit $kit, array $data, ?User $user, bool $used): array
    {
        if ($kit->status !== 'distributed') {
            return [
                'status' => 'error',
                'kit_id' => $kit->id,
                'message' => "Ce kit a le statut '{$kit->status}' — seul un kit distribué peut être confirmé utilisé.",
            ];
        }

        return DB::transaction(function () use ($kit, $data, $user, $used) {
            $kit->update([
                'status' => $used ? 'used' : 'not_used',
                'used_at' => $used ? $data['occurred_at'] : null,
            ]);

            if ($kit->beneficiary_id && $used) {
                $payload = $data['payload'] ?? [];
                Beneficiary::whereKey($kit->beneficiary_id)->update([
                    'actual_delivery_date' => $payload['delivery_date'] ?? $data['occurred_at'],
                    'delivery_location' => $payload['delivery_location'] ?? null,
                ]);
            }

            $this->recordEvent(
                $kit,
                $used ? 'used' : 'not_used',
                $data,
                $user,
                $kit->current_health_center_id,
                $kit->beneficiary_id
            );

            return [
                'status' => 'ok',
                'kit_id' => $kit->id,
                'message' => $used ? 'Utilisation confirmée.' : 'Non-utilisation enregistrée.',
            ];
        });
    }

    protected function resolveBeneficiary(array $data, ?User $user): Beneficiary
    {
        // Le mobile peut soit référencer une bénéficiaire déjà enregistrée
        // (beneficiary_id), soit envoyer ses infos pour en créer une à la
        // volée au moment de la distribution.
        if (! empty($data['beneficiary']['id'])) {
            return Beneficiary::findOrFail($data['beneficiary']['id']);
        }

        $b = $data['beneficiary'] ?? [];

        $beneficiary = Beneficiary::create([
            'first_name' => $b['first_name'] ?? 'Inconnue',
            'last_name' => $b['last_name'] ?? '',
            'phone' => $b['phone'] ?? null,
            'preferred_language' => $b['preferred_language'] ?? 'bambara',
            'health_center_id' => $b['health_center_id'] ?? $user?->health_center_id,
            'registered_by' => $user?->id,
            'expected_delivery_date' => $b['expected_delivery_date'] ?? null,
            'ivr_consent' => $b['ivr_consent'] ?? true,
        ]);

        $this->ivrScheduler->scheduleForBeneficiary($beneficiary);

        return $beneficiary;
    }

    protected function recordEvent(
        Kit $kit,
        string $type,
        array $data,
        ?User $user,
        ?int $healthCenterId,
        ?int $beneficiaryId
    ): void {
        KitEvent::create([
            'client_uuid' => $data['client_uuid'] ?? (string) Str::uuid(),
            'kit_id' => $kit->id,
            'user_id' => $user?->id,
            'health_center_id' => $healthCenterId,
            'beneficiary_id' => $beneficiaryId,
            'event_type' => $type,
            'payload' => $data['payload'] ?? null,
            'occurred_at' => $data['occurred_at'],
            'synced_at' => now(),
        ]);
    }
}
