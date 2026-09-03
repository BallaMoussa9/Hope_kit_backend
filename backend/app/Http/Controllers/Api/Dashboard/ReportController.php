<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(protected ReportGeneratorService $generator) {}

    /**
     * Historique des rapports générés — alimente la liste en bas de
     * l'écran "Rapports".
     */
    public function index(Request $request)
    {
        $reports = Report::with('generatedBy:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($reports);
    }

    /**
     * Génère un nouveau rapport — correspond au formulaire "Nouveau
     * Rapport" (Type de rapport / Période / Région-District-Centre).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['distribution', 'usage', 'performance_par_centre'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'health_center_id' => ['nullable', 'exists:health_centers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        $type = $validated['type'];
        unset($validated['type']);

        $report = $this->generator->generate($type, $validated, $request->user()?->id);

        return response()->json([
            'message' => 'Rapport généré avec succès.',
            'report' => $report,
            'download_url' => Storage::disk('public')->url($report->file_path),
        ], 201);
    }

    public function download(Report $report)
    {
        if (! $report->file_path || ! Storage::disk('public')->exists($report->file_path)) {
            abort(404, 'Fichier de rapport introuvable.');
        }

        return Storage::disk('public')->download(
            $report->file_path,
            Str::slug($report->title) . '.csv'
        );
    }
}
