<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CommercialDocument;
use App\Models\InventoryProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommercialDocumentController extends Controller
{
    private const TYPES = [
        'quote' => 'DEVIS',
        'proforma' => 'FACTURE PROFORMA',
        'invoice' => 'FACTURE DE SOLDE / CLÔTURE',
        'credit_note' => 'FACTURE D\'AVOIR',
    ];

    public function index(Request $r)
    {
        $q = CommercialDocument::with(['customer', 'lines.product'])->latest();
        if ($r->type) $q->where('type', $r->type);
        if ($r->search) {
            $q->where(function ($x) use ($r) {
                $x->where('document_number', 'like', '%' . $r->search . '%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%' . $r->search . '%'));
            });
        }
        return response()->json($q->paginate($r->integer('per_page', 20)));
    }

    public function show(CommercialDocument $document)
    {
        return response()->json($document->load(['customer', 'lines.product', 'source', 'creator']));
    }

    public function store(Request $r)
    {
        $v = $this->validateDoc($r);
        return DB::transaction(fn () => response()->json($this->createDocument($v, $r->user()->id), 201));
    }

    private function validateDoc(Request $r): array
    {
        return $r->validate([
            'type' => 'required|in:proforma,invoice,credit_note',
            'customer_id' => 'required|exists:customers,id',
            'source_document_id' => 'nullable|exists:commercial_documents,id',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'nullable|string|size:3',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'nullable|in:draft,sent,accepted,rejected,partially_paid,paid,closed,cancelled',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.inventory_product_id' => 'required|exists:inventory_products,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0',
        ]);
    }

    private function createDocument(array $v, int $userId): CommercialDocument
    {
        $subtotal = 0;
        foreach ($v['lines'] as $line) {
            $p = InventoryProduct::findOrFail($line['inventory_product_id']);
            $price = array_key_exists('unit_price', $line) ? $line['unit_price'] : $p->sale_price;
            $lt = max(0, ($line['quantity'] * $price) - ($line['discount'] ?? 0));
            $subtotal += $lt;
        }

        $discount = $v['discount'] ?? 0;
        $taxable = max(0, $subtotal - $discount);
        $tax = $taxable * (($v['tax_rate'] ?? 0) / 100);
        $total = $taxable + $tax;
        $paid = $v['paid_amount'] ?? 0;
        $type = $v['type'];
        $status = $v['status'] ?? 'draft';

        if ($type === 'invoice' && $paid >= $total && $total > 0) $status = 'closed';
        elseif ($type === 'invoice' && $paid > 0) $status = 'partially_paid';
        if ($type === 'credit_note') $status = $v['status'] ?? 'draft';

        $doc = CommercialDocument::create([
            'document_number' => $this->number($type),
            'type' => $type,
            'customer_id' => $v['customer_id'],
            'source_document_id' => $v['source_document_id'] ?? null,
            'issue_date' => $v['issue_date'] ?? now()->toDateString(),
            'due_date' => $v['due_date'] ?? null,
            'valid_until' => $v['valid_until'] ?? null,
            'discount' => $discount,
            'tax_rate' => $v['tax_rate'] ?? 0,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'paid_amount' => min($paid, $total),
            'balance_amount' => max(0, $total - $paid),
            'currency' => $v['currency'] ?? 'XOF',
            'payment_method' => $v['payment_method'] ?? null,
            'status' => $status,
            'notes' => $v['notes'] ?? null,
            'created_by' => $userId,
        ]);

        foreach ($v['lines'] as $line) {
            $p = InventoryProduct::findOrFail($line['inventory_product_id']);
            $price = array_key_exists('unit_price', $line) ? $line['unit_price'] : $p->sale_price;
            $lt = max(0, ($line['quantity'] * $price) - ($line['discount'] ?? 0));
            $doc->lines()->create([
                'inventory_product_id' => $p->id,
                'quantity' => $line['quantity'],
                'unit_price' => $price,
                'discount' => $line['discount'] ?? 0,
                'line_total' => $lt,
            ]);
        }

        if ($type === 'invoice' && in_array($status, ['accepted', 'partially_paid', 'closed'], true)) {
            $this->applyStock($doc, false);
            $doc->update(['stock_applied_at' => now()]);
        }
        if ($type === 'credit_note' && $status !== 'draft') {
            $this->applyStock($doc, true);
            $doc->update(['stock_applied_at' => now()]);
        }

        return $doc->load(['customer', 'lines.product', 'source']);
    }

    private function applyStock(CommercialDocument $doc, bool $restock): void
    {
        foreach ($doc->lines as $line) {
            $p = InventoryProduct::lockForUpdate()->findOrFail($line->inventory_product_id);
            $before = $p->stock_quantity;
            $qty = $line->quantity;
            $after = $restock ? $before + $qty : $before - $qty;
            if ($after < 0) abort(422, 'Stock insuffisant pour ' . $p->name);
            $p->update(['stock_quantity' => $after]);
            $p->movements()->create([
                'type' => $restock ? 'entry' : 'exit',
                'quantity' => $qty,
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_price' => $line->unit_price,
                'reference' => $doc->document_number,
                'reason' => $restock ? 'Avoir / retour client' : 'Facture de solde',
                'user_id' => $doc->created_by,
            ]);
        }
    }

    private function number(string $type): string
    {
        $prefix = ['quote' => 'DEV', 'proforma' => 'PRO', 'invoice' => 'FAC', 'credit_note' => 'AVO'][$type];
        return $prefix . '-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    public function convert(Request $r, CommercialDocument $document)
    {
        $type = $r->validate(['type' => 'required|in:invoice'])['type'];

        if ($document->type !== 'proforma') {
            abort(422, 'Seule une facture proforma validée peut devenir une facture.');
        }

        $document->load('lines');

        return DB::transaction(function () use ($document, $r, $type) {
            // Une proforma validée ne peut être transformée qu'une seule fois.
            if (CommercialDocument::where('source_document_id', $document->id)->where('type', 'invoice')->exists()) {
                abort(422, 'Cette facture proforma a déjà été transformée en facture.');
            }

            $document->update(['status' => 'accepted']);

            $data = [
                'type' => $type,
                'customer_id' => $document->customer_id,
                'source_document_id' => $document->id,
                'issue_date' => now()->toDateString(),
                'due_date' => $document->due_date,
                'currency' => $document->currency,
                'lines' => $document->lines->map(fn ($l) => [
                    'inventory_product_id' => $l->inventory_product_id,
                    'quantity' => $l->quantity,
                    'unit_price' => $l->unit_price,
                    'discount' => $l->discount,
                ])->values()->all(),
                'discount' => $document->discount,
                'tax_rate' => $document->tax_rate,
                // La facture est émise/validée mais reste payable.
                'status' => 'accepted',
                'notes' => 'Facture créée après validation de la proforma ' . $document->document_number,
            ];

            return response()->json($this->createDocument($data, $r->user()->id), 201);
        });
    }

    public function payment(Request $r, CommercialDocument $document)
    {
        if ($document->type !== 'invoice') abort(422, 'Les paiements concernent uniquement les factures de solde/clôture.');
        if (in_array($document->status, ['closed', 'cancelled'], true)) abort(422, 'Cette facture ne peut plus recevoir de paiement.');

        $v = $r->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
        ]);

        return DB::transaction(function () use ($document, $v) {
            $document->lockForUpdate()->first();
            $amount = (float) $v['amount'];
            $balance = (float) $document->balance_amount;
            if ($amount > $balance + 0.01) abort(422, 'Le paiement dépasse le solde restant.');

            $paid = (float) $document->paid_amount + $amount;
            $newBalance = max(0, (float) $document->total_amount - $paid);
            $document->update([
                'paid_amount' => $paid,
                'balance_amount' => $newBalance,
                'payment_method' => $v['payment_method'] ?? $document->payment_method,
                'status' => $newBalance <= 0.01 ? 'closed' : 'partially_paid',
            ]);

            if ($document->status === 'closed' && !$document->stock_applied_at) {
                $document->load('lines');
                $this->applyStock($document, false);
                $document->update(['stock_applied_at' => now()]);
            }

            return response()->json($document->fresh()->load(['customer', 'lines.product', 'source']));
        });
    }

    public function creditNotePreview(CommercialDocument $document)
    {
        if ($document->type !== 'invoice') abort(422, 'La prévisualisation d’un avoir nécessite une facture.');
        $document->load('lines.product');
        $credited = $this->creditedQuantities($document);
        $lines = $document->lines->map(fn ($line) => [
            'inventory_product_id' => $line->inventory_product_id,
            'product_name' => $line->product?->name ?? 'Produit',
            'unit_price' => (float) $line->unit_price,
            'discount' => (float) $line->discount,
            'original_quantity' => (int) $line->quantity,
            'already_credited' => (int) ($credited[$line->inventory_product_id] ?? 0),
            'max_quantity' => max(0, (int) $line->quantity - (int) ($credited[$line->inventory_product_id] ?? 0)),
        ]);
        return response()->json([
            'invoice' => $document->load('customer'),
            'lines' => $lines,
            'credited_amount' => $this->creditedAmount($document),
            'creditable_amount' => max(0, (float) $document->total_amount - $this->creditedAmount($document)),
        ]);
    }

    /**
     * Prepare an avoir from an invoice. Amounts are calculated from the original invoice.
     * The user only chooses returned quantities. A product cannot be credited more than
     * the original quantity minus quantities already credited by previous avoirs.
     */
    public function createCreditNote(Request $r, CommercialDocument $document)
    {
        if ($document->type !== 'invoice') abort(422, 'Un avoir doit être créé à partir d’une facture de solde/clôture.');

        $document->load('lines');
        $lines = $r->validate([
            'lines' => 'required|array|min:1',
            'lines.*.inventory_product_id' => 'required|exists:inventory_products,id',
            'lines.*.quantity' => 'required|integer|min:1',
        ])['lines'];

        $alreadyCredited = $this->creditedQuantities($document);
        $sourceByProduct = $document->lines->keyBy('inventory_product_id');
        $prepared = [];

        foreach ($lines as $line) {
            $source = $sourceByProduct->get($line['inventory_product_id']);
            if (!$source) abort(422, 'Le produit sélectionné ne figure pas sur la facture d’origine.');

            $max = max(0, (int) $source->quantity - ($alreadyCredited[$source->inventory_product_id] ?? 0));
            if ((int) $line['quantity'] > $max) {
                abort(422, 'Quantité d’avoir trop élevée pour ' . ($source->product->name ?? 'le produit') . '. Maximum restant : ' . $max . '.');
            }

            $prepared[] = [
                'inventory_product_id' => $source->inventory_product_id,
                'quantity' => (int) $line['quantity'],
                'unit_price' => (float) $source->unit_price,
                'discount' => (float) $source->discount,
            ];
        }

        $totalSource = (float) $document->total_amount;
        $sourceSubtotal = max(0.01, (float) $document->subtotal);
        $creditSubtotal = collect($prepared)->sum(fn ($l) => ($l['quantity'] * $l['unit_price']) - min($l['discount'], $l['quantity'] * $l['unit_price']));
        $ratio = min(1, $creditSubtotal / $sourceSubtotal);
        $creditDiscount = min((float) $document->discount, (float) $document->discount * $ratio);
        $taxable = max(0, $creditSubtotal - $creditDiscount);
        $tax = $taxable * ((float) $document->tax_rate / 100);
        $creditTotal = $taxable + $tax;

        if ($creditTotal <= 0) abort(422, 'Le montant de l’avoir doit être supérieur à zéro.');

        $creditableTotal = $totalSource - $this->creditedAmount($document);
        if ($creditTotal > $creditableTotal + 0.01) {
            abort(422, 'Le montant total des avoirs dépasserait le montant de la facture d’origine.');
        }

        $data = [
            'type' => 'credit_note',
            'customer_id' => $document->customer_id,
            'source_document_id' => $document->id,
            'issue_date' => now()->toDateString(),
            'currency' => $document->currency,
            'lines' => $prepared,
            'discount' => round($creditDiscount, 2),
            'tax_rate' => $document->tax_rate,
            'status' => 'closed',
            'notes' => 'Avoir calculé automatiquement depuis la facture ' . $document->document_number,
        ];

        return DB::transaction(fn () => response()->json($this->createDocument($data, $r->user()->id), 201));
    }

    private function creditedQuantities(CommercialDocument $invoice): array
    {
        return CommercialDocument::where('type', 'credit_note')
            ->where('source_document_id', $invoice->id)
            ->with('lines')
            ->get()
            ->flatMap->lines
            ->groupBy('inventory_product_id')
            ->map(fn ($items) => (int) $items->sum('quantity'))
            ->all();
    }

    private function creditedAmount(CommercialDocument $invoice): float
    {
        return (float) CommercialDocument::where('type', 'credit_note')
            ->where('source_document_id', $invoice->id)
            ->sum('total_amount');
    }

    public function print(CommercialDocument $document)
    {
        $document->load(['customer', 'lines.product', 'source']);
        return response()->view('commercial.document', [
            'document' => $document,
            'logoUrl' => url('/images/hope-logo.png'),
            'types' => self::TYPES,
        ]);
    }
}
