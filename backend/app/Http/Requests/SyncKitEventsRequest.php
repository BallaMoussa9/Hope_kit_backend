<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncKitEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'min:1', 'max:200'],
            'events.*.client_uuid' => ['required', 'uuid'],
            'events.*.qr_code' => ['required', 'string'],
            'events.*.event_type' => ['required', 'in:received,distributed,used,not_used'],
            'events.*.occurred_at' => ['required', 'date'],
            'events.*.payload' => ['nullable', 'array'],
            'events.*.beneficiary' => ['nullable', 'array'],
        ];
    }
}
