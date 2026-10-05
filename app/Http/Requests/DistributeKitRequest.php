<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DistributeKitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // le rôle est déjà vérifié par le middleware de route
    }

    public function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'occurred_at' => ['required', 'date'],
            'health_center_id' => ['nullable', 'exists:health_centers,id'],

            'beneficiary_id' => ['nullable', 'exists:beneficiaries,id'],
            'beneficiary.first_name' => ['required_without:beneficiary_id', 'string', 'max:255'],
            'beneficiary.last_name' => ['nullable', 'string', 'max:255'],
            'beneficiary.phone' => ['nullable', 'string', 'max:30'],
            'beneficiary.preferred_language' => ['nullable', 'in:bambara,peulh,soninke,francais,autre'],
            'beneficiary.expected_delivery_date' => ['nullable', 'date'],
            'beneficiary.ivr_consent' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Reformate la requête au format attendu par KitEventProcessor.
     */
    public function toEventPayload(string $qrCode): array
    {
        $validated = $this->validated();

        return [
            'client_uuid' => $validated['client_uuid'] ?? (string) Str::uuid(),
            'qr_code' => $qrCode,
            'event_type' => 'distributed',
            'occurred_at' => $validated['occurred_at'],
            'payload' => ['health_center_id' => $validated['health_center_id'] ?? null],
            'beneficiary' => array_merge(
                $validated['beneficiary'] ?? [],
                ['id' => $validated['beneficiary_id'] ?? null]
            ),
        ];
    }
}
