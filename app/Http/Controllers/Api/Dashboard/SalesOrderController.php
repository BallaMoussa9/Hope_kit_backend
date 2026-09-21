<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\InventoryProduct;
use App\Models\SalesOrder;
use App\Models\WarehouseStock;
use App\Services\DocumentNumberService;
use App\Services\SimplePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    private function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'order_date' => 'nullable|date',
            'expected_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.inventory_product_id' => 'required|exists:inventory_products,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.discount' => 'nullable|numeric|min:0',
        ];
    }

    public function index(Request $r)
    {
        $q = SalesOrder::with(['customer', 'warehouse', 'lines.product', 'documents', 'deliveries'])->latest();
        if ($r->status) $q->where('status', $r->status);
        if ($r->search) $q->where(fn ($x) => $x->where('order_number', 'like', '%' . $r->search . '%')->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%' . $r->search . '%')));
        return response()->json($q->paginate($r->integer('per_page', 25)));
    }

    public function show(SalesOrder $order)
    {
        return response()->json($order->load(['customer', 'warehouse', 'lines.product', 'documents', 'deliveries']));
    }

    private function totals(array $lines, float $discount): array
    {
        $subtotal = 0;
        foreach ($lines as $line) {
            $product = InventoryProduct::findOrFail($line['inventory_product_id']);
            $price = array_key_exists('unit_price', $line) ? (float) $line['unit_price'] : (float) $product->sale_price;
            $subtotal += max(0, ((int) $line['quantity'] * $price) - (float) ($line['discount'] ?? 0));
        }
        return [$subtotal, max(0, $subtotal - $discount)];
    }

    public function store(Request $r, DocumentNumberService $numbers)
    {
        $v = $r->validate($this->rules());
        return DB::transaction(function () use ($v, $r, $numbers) {
            [$subtotal, $total] = $this->totals($v['lines'], (float) ($v['discount'] ?? 0));
            $order = SalesOrder::create([
                'order_number' => $numbers->next('sales_order', 'CMD'),
                'customer_id' => $v['customer_id'], 'warehouse_id' => $v['warehouse_id'] ?? null,
                'status' => 'draft', 'order_date' => $v['order_date'] ?? now()->toDateString(),
                'expected_date' => $v['expected_date'] ?? null, 'discount' => $v['discount'] ?? 0,
                'subtotal' => $subtotal, 'total_amount' => $total, 'currency' => $v['currency'] ?? 'XOF',
                'notes' => $v['notes'] ?? null, 'created_by' => $r->user()->id,
            ]);
            $this->replaceLines($order, $v['lines']);
            return response()->json($order->load(['customer', 'warehouse', 'lines.product']), 201);
        });
    }

    public function update(Request $r, SalesOrder $order)
    {
        abort_unless($order->status === 'draft', 422, 'Seule une commande brouillon peut être modifiée.');
        $v = $r->validate($this->rules());
        return DB::transaction(function () use ($v, $order) {
            [$subtotal, $total] = $this->totals($v['lines'], (float) ($v['discount'] ?? 0));
            $order->update([
                'customer_id' => $v['customer_id'], 'warehouse_id' => $v['warehouse_id'] ?? null,
                'order_date' => $v['order_date'] ?? $order->order_date, 'expected_date' => $v['expected_date'] ?? null,
                'discount' => $v['discount'] ?? 0, 'subtotal' => $subtotal, 'total_amount' => $total,
                'currency' => $v['currency'] ?? 'XOF', 'notes' => $v['notes'] ?? null,
            ]);
            $order->lines()->delete();
            $this->replaceLines($order, $v['lines']);
            return response()->json($order->fresh(['customer', 'warehouse', 'lines.product']));
        });
    }

    private function replaceLines(SalesOrder $order, array $lines): void
    {
        foreach ($lines as $line) {
            $p = InventoryProduct::findOrFail($line['inventory_product_id']);
            $price = array_key_exists('unit_price', $line) ? $line['unit_price'] : $p->sale_price;
            $total = max(0, ((int) $line['quantity'] * (float) $price) - (float) ($line['discount'] ?? 0));
            $order->lines()->create(['inventory_product_id' => $p->id, 'quantity' => $line['quantity'], 'unit_price' => $price, 'discount' => $line['discount'] ?? 0, 'line_total' => $total]);
        }
    }

    public function validateOrder(SalesOrder $order)
    {
        abort_unless($order->status === 'draft', 422, 'Seule une commande brouillon peut être validée.');
        return DB::transaction(function () use ($order) {
            $order->load(['lines.product']);
            if ($order->warehouse_id) foreach ($order->lines as $line) {
                $ws = WarehouseStock::lockForUpdate()->firstOrCreate(['warehouse_id' => $order->warehouse_id, 'inventory_product_id' => $line->inventory_product_id], ['quantity' => 0, 'reserved_quantity' => 0]);
                if (($ws->quantity - $ws->reserved_quantity) < $line->quantity) abort(422, 'Stock disponible insuffisant pour ' . ($line->product->name ?? 'le produit'));
                $ws->increment('reserved_quantity', $line->quantity);
            }
            $order->update(['status' => 'validated']);
            return response()->json($order->fresh(['customer', 'warehouse', 'lines.product']));
        });
    }

    public function setStatus(Request $r, SalesOrder $order)
    {
        $status = $r->validate(['status' => 'required|in:draft,validated,processing,partially_delivered,delivered,cancelled'])['status'];
        $allowed = [
            'draft' => ['draft','validated','cancelled'],
            'validated' => ['validated','processing','cancelled'],
            'processing' => ['processing','cancelled'],
            'partially_delivered' => ['partially_delivered'],
            'delivered' => ['delivered'],
            'cancelled' => ['cancelled'],
        ];
        abort_unless(in_array($status, $allowed[$order->status] ?? [], true), 422, 'Transition de statut non autorisée.');
        $order->update(['status' => $status]);
        return response()->json($order->fresh(['customer','warehouse','lines.product']));
    }

    public function cancel(SalesOrder $order)
    {
        abort_if(in_array($order->status, ['delivered', 'cancelled']), 422, 'Cette commande ne peut plus être annulée.');
        return DB::transaction(function () use ($order) {
            $order->load('lines');
            if ($order->warehouse_id && in_array($order->status, ['validated', 'processing', 'partially_delivered'])) foreach ($order->lines as $line) {
                $ws = WarehouseStock::lockForUpdate()->where(['warehouse_id' => $order->warehouse_id, 'inventory_product_id' => $line->inventory_product_id])->first();
                if ($ws) $ws->update(['reserved_quantity' => max(0, $ws->reserved_quantity - max(0, $line->quantity - $line->delivered_quantity))]);
            }
            $order->update(['status' => 'cancelled']);
            return response()->json($order->fresh());
        });
    }

    public function invoice(SalesOrder $order)
    {
        abort_unless(in_array($order->status, ['validated', 'processing', 'partially_delivered', 'delivered']), 422, 'Validez la commande avant de créer la facture.');
        if ($order->documents()->where('type', 'invoice')->exists()) abort(422, 'Cette commande possède déjà une facture.');
        $order->load('lines');
        $request = Request::create('/', 'POST', ['type' => 'invoice', 'customer_id' => $order->customer_id, 'sales_order_id' => $order->id, 'issue_date' => now()->toDateString(), 'currency' => $order->currency, 'status' => 'accepted', 'notes' => 'Facture issue de la commande ' . $order->order_number, 'lines' => $order->lines->map(fn ($l) => ['inventory_product_id' => $l->inventory_product_id, 'quantity' => $l->quantity, 'unit_price' => $l->unit_price, 'discount' => $l->discount])->values()->all()]);
        $request->setUserResolver(fn () => request()->user());
        return app(CommercialDocumentController::class)->store($request);
    }

    public function pdf(SalesOrder $order, SimplePdfService $pdf)
    {
        return response($pdf->salesOrder($order), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$order->order_number.'.pdf"','Cache-Control'=>'no-store']);
    }

    public function email(Request $request, SalesOrder $order, SimplePdfService $pdf)
    {
        $to = $request->validate(['email'=>'required|email'])['email'];
        if (config('mail.default') === 'log') abort(422, 'SMTP non configuré. Configurez les paramètres MAIL_* dans .env.');
        $order->load(['customer','lines.product','warehouse']); $bytes=$pdf->salesOrder($order);
        try { Mail::send('emails.document-available',['title'=>'Commande HOPE '.$order->order_number,'document'=>$order,'recipient'=>$to],function($m)use($to,$bytes,$order){$m->to($to)->subject('HOPE - Commande '.$order->order_number)->attachData($bytes,$order->order_number.'.pdf',['mime'=>'application/pdf']);}); return response()->json(['message'=>'Commande envoyée à '.$to.'.']); }
        catch (\Throwable $e) { report($e); return response()->json(['message'=>'Échec de l’envoi email. Vérifiez la configuration SMTP.','error'=>app()->isLocal()?$e->getMessage():null],500); }
    }

}
