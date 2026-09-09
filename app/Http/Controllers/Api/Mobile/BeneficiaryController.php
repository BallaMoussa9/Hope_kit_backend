<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Beneficiary;
use App\Services\Ivr\IvrScheduler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficiaryController extends Controller
{
    public function __construct(protected IvrScheduler $ivrScheduler) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'preferred_language' => ['required', Rule::in(['bambara', 'peulh', 'soninke', 'francais', 'autre'])],
            'expected_delivery_date' => ['required', 'date', 'after:today'],
            'health_center_id' => ['nullable', 'exists:health_centers,id'],
            'ivr_consent' => ['required', 'boolean'],
        ]);

        $beneficiary = Beneficiary::create([
            ...$validated,
            'health_center_id' => $validated['health_center_id'] ?? $request->user()?->health_center_id,
            'registered_by' => $request->user()?->id,
        ]);

        // Planifie automatiquement tous les rappels CPN + accouchement
        $callsScheduled = $this->ivrScheduler->scheduleForBeneficiary($beneficiary);

        return response()->json([
            'message' => 'Bénéficiaire enregistrée.',
            'beneficiary' => $beneficiary,
            'ivr_calls_scheduled' => $callsScheduled,
        ], 201);
    }

    public function search(Request $request)
    {
        $q = $request->query('q', '');

        $beneficiaries = Beneficiary::query()
            ->when($q, fn ($query) => $query
                ->where('phone', 'like', "%{$q}%")
                ->orWhere('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%"))
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'phone', 'expected_delivery_date']);

        return response()->json($beneficiaries);
    }
}
