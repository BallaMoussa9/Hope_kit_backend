<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $alerts = Alert::with('healthCenter:id,name', 'kit:id,qr_code')
            ->when($request->query('status', 'open'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('type'), fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('detected_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($alerts);
    }

    public function resolve(Request $request, Alert $alert)
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()?->id,
        ]);

        return response()->json(['message' => 'Alerte résolue.', 'alert' => $alert]);
    }
}
