<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CommercialDocument;
use App\Models\InventoryProduct;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Services\SimplePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ErpReportController extends Controller
{
    public function index(Request $r)
    {
        $type = $r->validate(['type' => 'nullable|in:sales,stock,movements,purchases,payments'])['type'] ?? 'sales';
        [$from, $to] = $this->period($r);
        return response()->json(match ($type) {
            'stock' => $this->stockReport(),
            'movements' => $this->movementReport($from, $to),
            'purchases' => $this->purchaseReport($from, $to),
            'payments' => $this->paymentReport($from, $to),
            default => $this->salesReport($from, $to),
        });
    }

    public function print(Request $r, SimplePdfService $pdf)
    {
        $type = $r->input('type', 'sales'); [$from,$to]=$this->period($r); $data=$this->index($r)->getData(true);
        return response($pdf->report($type,$data,$from,$to),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="rapport-'.$type.'.pdf"','Cache-Control'=>'no-store']);
    }

    public function email(Request $r, SimplePdfService $pdf)
    {
        $v = $r->validate([
            'email' => 'required|email',
            'type' => 'nullable|in:sales,stock,movements,purchases,payments',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if (config('mail.default') === 'log') {
            abort(422, 'SMTP non configuré. Configurez les paramètres MAIL_* dans .env.');
        }

        $type = $v['type'] ?? 'sales';
        [$from, $to] = $this->period($r);
        $data = $this->index($r)->getData(true);

        try {
            Mail::to($v['email'])->queue(new \App\Mail\ReportEmailMail(
                $type,
                $data,
                $from->toIso8601String(),
                $to->toIso8601String(),
                $v['email'],
            ));

            return response()->json([
                'message' => 'Le rapport a été placé dans la file d’envoi.',
                'recipient' => $v['email'],
                'status' => 'queued',
            ], 202);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'message' => 'Impossible de placer le rapport dans la file d’envoi.',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function period(Request $r): array
    {
        $from = $r->date_from ? now()->parse($r->date_from)->startOfDay() : now()->startOfMonth();
        $to = $r->date_to ? now()->parse($r->date_to)->endOfDay() : now()->endOfDay();
        return [$from, $to];
    }

    private function salesReport($from, $to): array
    {
        $orders = SalesOrder::with('customer')->whereBetween('created_at', [$from, $to])->latest()->get();
        $invoices = CommercialDocument::with('customer')->where('type', 'invoice')->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()])->latest()->get();
        return [
            'summary' => [[
                'Commandes' => $orders->count(),
                'Montant commandes' => round((float) $orders->sum('total_amount'), 2) . ' XOF',
                'Factures' => $invoices->count(),
                'Montant facturé' => round((float) $invoices->sum('total_amount'), 2) . ' XOF',
                'Montant payé' => round((float) $invoices->sum('paid_amount'), 2) . ' XOF',
            ]],
            'commandes' => $orders->map(fn ($o) => [
                'N° commande' => $o->order_number,
                'Client' => $o->customer?->name ?? '—',
                'Date' => optional($o->order_date)->format('d/m/Y'),
                'Statut' => $this->status($o->status),
                'Total' => number_format((float) $o->total_amount, 0, ',', ' ') . ' XOF',
            ])->values()->all(),
            'factures' => $invoices->map(fn ($d) => [
                'N° facture' => $d->document_number,
                'Client' => $d->customer?->name ?? '—',
                'Date' => optional($d->issue_date)->format('d/m/Y'),
                'Total' => number_format((float) $d->total_amount, 0, ',', ' ') . ' XOF',
                'Payé' => number_format((float) $d->paid_amount, 0, ',', ' ') . ' XOF',
                'Solde' => number_format((float) $d->balance_amount, 0, ',', ' ') . ' XOF',
            ])->values()->all(),
        ];
    }

    private function stockReport(): array
    {
        $products = InventoryProduct::with('warehouseStocks.warehouse')->where('is_active', true)->orderBy('name')->get();
        return [
            'summary' => [[
                'Produits actifs' => $products->count(),
                'Produits sous seuil' => $products->filter(fn ($p) => (int) $p->stock_quantity <= (int) $p->stock_min)->count(),
                'Unités en stock' => (int) $products->sum('stock_quantity'),
            ]],
            'stock' => $products->map(fn ($p) => [
                'Produit' => $p->name,
                'Référence' => $p->reference ?? '—',
                'Stock global' => (int) $p->stock_quantity,
                'Seuil minimum' => (int) $p->stock_min,
                'État' => (int) $p->stock_quantity <= (int) $p->stock_min ? 'À réapprovisionner' : 'Normal',
                'Entrepôts' => $p->warehouseStocks->map(fn ($s) => ($s->warehouse?->name ?? 'Entrepôt') . ': ' . $s->quantity . ' (réservé ' . $s->reserved_quantity . ')')->implode(' ; '),
            ])->values()->all(),
        ];
    }

    private function movementReport($from, $to): array
    {
        $rows = StockMovement::with(['product', 'warehouse', 'user'])->whereBetween('created_at', [$from, $to])->latest()->get();
        return [
            'summary' => [[
                'Mouvements' => $rows->count(),
                'Entrées' => (int) $rows->where('type', 'entry')->sum('quantity'),
                'Sorties' => (int) $rows->where('type', 'exit')->sum('quantity'),
            ]],
            'mouvements' => $rows->map(fn ($m) => [
                'Date' => optional($m->created_at)->format('d/m/Y H:i'),
                'Produit' => $m->product?->name ?? '—',
                'Entrepôt' => $m->warehouse?->name ?? '—',
                'Opération' => $this->status($m->type),
                'Quantité' => (int) $m->quantity,
                'Référence' => $m->reference ?: '—',
                'Motif' => $m->reason ?: '—',
                'Agent' => $m->user?->name ?? '—',
            ])->values()->all(),
        ];
    }

    private function purchaseReport($from, $to): array
    {
        $rows = PurchaseOrder::with('supplier')->whereBetween('created_at', [$from, $to])->latest()->get();
        return [
            'summary' => [[
                'Commandes fournisseurs' => $rows->count(),
                'Total achats' => number_format((float) $rows->sum('total_amount'), 0, ',', ' ') . ' XOF',
            ]],
            'achats' => $rows->map(fn ($o) => [
                'N° commande' => $o->order_number,
                'Fournisseur' => $o->supplier?->name ?? '—',
                'Date' => optional($o->order_date)->format('d/m/Y'),
                'Statut' => $this->status($o->status),
                'Total' => number_format((float) $o->total_amount, 0, ',', ' ') . ' XOF',
            ])->values()->all(),
        ];
    }

    private function paymentReport($from, $to): array
    {
        $rows = Payment::with(['document', 'customer', 'recorder'])->whereBetween('paid_at', [$from, $to])->latest()->get();
        $activeRows = $rows->where('status', '!=', 'cancelled');
        return [
            'summary' => [[
                'Transactions' => $activeRows->count(),
                'Montant encaissé' => number_format((float) $activeRows->sum('amount'), 0, ',', ' ') . ' XOF',
            ]],
            'paiements' => $rows->map(fn ($p) => [
                'Date' => optional($p->paid_at)->format('d/m/Y H:i'),
                'Client' => $p->customer?->name ?? '—',
                'Facture' => $p->document?->document_number ?? '—',
                'Montant' => number_format((float) $p->amount, 0, ',', ' ') . ' XOF',
                'Mode' => $this->paymentMethod($p->method),
                'Référence' => $p->reference ?: '—',
                'Statut' => $this->paymentStatus($p->status),
                'Agent' => $p->recorder?->name ?? '—',
            ])->values()->all(),
        ];
    }

    private function label(string $type): string
    {
        return ['sales' => 'Ventes', 'stock' => 'État du stock', 'movements' => 'Mouvements de stock', 'purchases' => 'Achats', 'payments' => 'Paiements'][$type] ?? $type;
    }
    private function labelSection(string $section): string { return ['summary' => 'Synthèse', 'commandes' => 'Commandes', 'factures' => 'Factures', 'stock' => 'Stock', 'mouvements' => 'Mouvements', 'achats' => 'Achats', 'paiements' => 'Paiements'][$section] ?? $section; }
    private function status(?string $v): string { return ['draft' => 'Brouillon', 'validated' => 'Validée', 'processing' => 'En traitement', 'partially_delivered' => 'Partiellement livrée', 'delivered' => 'Livrée', 'cancelled' => 'Annulée', 'entry' => 'Entrée', 'exit' => 'Sortie', 'adjustment' => 'Ajustement', 'transfer' => 'Transfert', 'partially_received' => 'Partiellement reçue', 'received' => 'Reçue'][$v] ?? (string) $v; }
    private function paymentStatus(?string $v): string { return ['recorded' => 'Enregistré', 'validated' => 'Validé', 'cancelled' => 'Annulé'][$v] ?? (string) $v; }
    private function paymentMethod(?string $v): string { return ['orange_money' => 'Orange Money', 'moov_money' => 'Moov Money', 'cash' => 'Espèces', 'bank_transfer' => 'Virement bancaire', 'check' => 'Chèque', 'paypal' => 'PayPal', 'other' => 'Autre'][$v] ?? (string) $v; }
}
