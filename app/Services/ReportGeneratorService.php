<?php

namespace App\Services;

use App\Models\HealthCenter;
use App\Models\Kit;
use App\Models\Report;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportGeneratorService
{
    public function generate(string $type, array $filters, ?int $userId): Report
    {
        [$rows, $headers] = match ($type) {
            'distribution' => $this->buildDistributionReport($filters),
            'usage' => $this->buildUsageReport($filters),
            'performance_par_centre' => $this->buildPerformanceReport($filters),
            default => throw new \InvalidArgumentException("Type de rapport inconnu : {$type}"),
        };

        $fileName = 'reports/' . Str::slug($type) . '-' . now()->format('Ymd-His') . '-' . Str::random(6) . '.csv';
        $this->writeCsv($fileName, $headers, $rows);

        return Report::create([
            'type' => $type,
            'title' => $this->titleFor($type, $filters),
            'filters' => $filters,
            'format' => 'csv',
            'file_path' => $fileName,
            'row_count' => count($rows),
            'generated_by' => $userId,
        ]);
    }

    protected function buildDistributionReport(array $filters): array
    {
        $kits = $this->applyFilters(
            Kit::with(['healthCenter.district.region', 'beneficiary', 'project'])
                ->whereNotNull('distributed_at'),
            $filters
        )->get();

        $headers = ['QR Code', 'Projet', 'Région', 'District', 'Centre de santé', 'Bénéficiaire', 'Téléphone', 'Date de distribution', 'Statut'];

        $rows = $kits->map(fn (Kit $k) => [
            $k->qr_code,
            $k->project?->name,
            $k->healthCenter?->district?->region?->name,
            $k->healthCenter?->district?->name,
            $k->healthCenter?->name,
            $k->beneficiary?->full_name,
            $k->beneficiary?->phone,
            optional($k->distributed_at)->format('Y-m-d'),
            $k->status,
        ])->toArray();

        return [$rows, $headers];
    }

    protected function buildUsageReport(array $filters): array
    {
        $kits = $this->applyFilters(
            Kit::with(['healthCenter.district.region', 'beneficiary'])
                ->whereIn('status', ['used', 'not_used']),
            $filters
        )->get();

        $headers = ['QR Code', 'Centre de santé', 'Bénéficiaire', 'Statut', 'Date de distribution', "Date d'utilisation", 'Délai (jours)'];

        $rows = $kits->map(fn (Kit $k) => [
            $k->qr_code,
            $k->healthCenter?->name,
            $k->beneficiary?->full_name,
            $k->status === 'used' ? 'Utilisé' : 'Non utilisé',
            optional($k->distributed_at)->format('Y-m-d'),
            optional($k->used_at)->format('Y-m-d'),
            $k->days_in_stock,
        ])->toArray();

        return [$rows, $headers];
    }

    protected function buildPerformanceReport(array $filters): array
    {
        $centers = HealthCenter::with('district.region')
            ->withCount([
                'kits as total_kits',
                'kits as distributed_kits' => fn ($q) => $q->whereIn('status', ['distributed', 'used', 'not_used']),
                'kits as used_kits' => fn ($q) => $q->where('status', 'used'),
            ])
            ->get();

        $headers = ['Centre de santé', 'District', 'Région', 'Total kits', 'Distribués', 'Utilisés', "Taux d'utilisation (%)"];

        $rows = $centers->map(function ($c) {
            $taux = $c->distributed_kits > 0 ? round(($c->used_kits / $c->distributed_kits) * 100, 1) : 0;

            return [
                $c->name,
                $c->district?->name,
                $c->district?->region?->name,
                $c->total_kits,
                $c->distributed_kits,
                $c->used_kits,
                $taux,
            ];
        })->toArray();

        return [$rows, $headers];
    }

    protected function applyFilters($query, array $filters)
    {
        return $query
            ->when($filters['health_center_id'] ?? null, fn ($q, $v) => $q->where('current_health_center_id', $v))
            ->when($filters['district_id'] ?? null, function ($q, $v) {
                $q->whereHas('healthCenter', fn ($hc) => $hc->where('district_id', $v));
            })
            ->when($filters['region_id'] ?? null, function ($q, $v) {
                $q->whereHas('healthCenter.district', fn ($d) => $d->where('region_id', $v));
            })
            ->when($filters['project_id'] ?? null, fn ($q, $v) => $q->where('project_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', $v));
    }

    protected function writeCsv(string $relativePath, array $headers, array $rows): void
    {
        $handle = fopen('php://temp', 'w+');

        // BOM UTF-8 pour qu'Excel affiche correctement les accents français
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($handle, $row, ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($relativePath, $content);
    }

    protected function titleFor(string $type, array $filters): string
    {
        $labels = [
            'distribution' => 'Rapport de distribution',
            'usage' => "Rapport d'utilisation",
            'performance_par_centre' => 'Performance par centre',
        ];

        $period = ($filters['date_from'] ?? null) && ($filters['date_to'] ?? null)
            ? " ({$filters['date_from']} → {$filters['date_to']})"
            : '';

        return ($labels[$type] ?? $type) . $period;
    }
}
