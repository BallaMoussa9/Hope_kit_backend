<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ConfirmKitUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'occurred_at' => ['required', 'date'],
            'used' => ['required', 'boolean'],
            'delivery_date' => ['required_if:used,true', 'nullable', 'date'],
            'delivery_location' => ['required_if:used,true', 'nullable', 'in:domicile,centre_de_sante,autre'],
            'reason_not_used' => ['required_if:used,false', 'nullable', 'string', 'max:255'],
        ];
    }

    public function toEventPayload(string $qrCode): array
    {
        $validated = $this->validated();

        return [
            'client_uuid' => $validated['client_uuid'] ?? (string) Str::uuid(),
            'qr_code' => $qrCode,
            'event_type' => $validated['used'] ? 'used' : 'not_used',
            'occurred_at' => $validated['occurred_at'],
            'payload' => [
                'delivery_date' => $validated['delivery_date'] ?? null,
                'delivery_location' => $validated['delivery_location'] ?? null,
                'reason_not_used' => $validated['reason_not_used'] ?? null,
            ],
        ];
    }
}
