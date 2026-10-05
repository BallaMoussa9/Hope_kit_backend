<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\IvrCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IvrController extends Controller
{
    public function stats(Request $request)
    {
        $counts = IvrCall::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byLanguage = IvrCall::query()
            ->join('beneficiaries', 'ivr_calls.beneficiary_id', '=', 'beneficiaries.id')
            ->select('beneficiaries.preferred_language', DB::raw('count(*) as total'))
            ->groupBy('beneficiaries.preferred_language')
            ->pluck('total', 'preferred_language');

        $answered = $counts['answered'] ?? 0;
        $sentTotal = ($counts['answered'] ?? 0) + ($counts['no_answer'] ?? 0) + ($counts['failed'] ?? 0);

        return response()->json([
            'par_statut' => $counts,
            'par_langue' => $byLanguage,
            'taux_de_reponse' => $sentTotal > 0 ? round(($answered / $sentTotal) * 100, 1) : 0,
        ]);
    }

    public function index(Request $request)
    {
        $calls = IvrCall::with('beneficiary:id,first_name,last_name,phone,preferred_language')
            ->when($request->query('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('call_type'), fn ($q, $v) => $q->where('call_type', $v))
            ->orderByDesc('scheduled_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($calls);
    }
}
