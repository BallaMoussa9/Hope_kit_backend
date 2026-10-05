<?php
namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SalesOrder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function order(Request $request, SalesOrder $order)
    {
        $docIds = $order->documents()->pluck('id');
        $deliveryIds = $order->deliveries()->pluck('id');
        $paymentIds = \App\Models\Payment::whereIn('commercial_document_id', $docIds)->pluck('id');
        $types = [SalesOrder::class, \App\Models\CommercialDocument::class, \App\Models\DeliveryNote::class, \App\Models\Payment::class];
        $ids = [$order->id, ...$docIds->all(), ...$deliveryIds->all(), ...$paymentIds->all()];
        $logs = AuditLog::with('user:id,name,email')->where(function($q) use($order,$docIds,$deliveryIds,$paymentIds) {
            $q->where(fn($x)=>$x->where('auditable_type',SalesOrder::class)->where('auditable_id',$order->id))
              ->orWhere(fn($x)=>$x->where('auditable_type',\App\Models\CommercialDocument::class)->whereIn('auditable_id',$docIds))
              ->orWhere(fn($x)=>$x->where('auditable_type',\App\Models\DeliveryNote::class)->whereIn('auditable_id',$deliveryIds))
              ->orWhere(fn($x)=>$x->where('auditable_type',\App\Models\Payment::class)->whereIn('auditable_id',$paymentIds));
        })->latest('id')->paginate(min(max($request->integer('per_page',50),1),100));
        return response()->json($logs);
    }

    public function index(Request $request)
    {
        $query = AuditLog::query()->with('user:id,name,email')->latest('id');
        if ($request->filled('user_id')) $query->where('user_id', $request->integer('user_id'));
        if ($request->filled('action')) $query->where('action', 'like', '%'.str_replace(['%','_'], ['\\%','\\_'], $request->string('action')->toString()).'%');
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->date('to'));
        if ($request->filled('auditable_type')) $query->where('auditable_type', $request->string('auditable_type'));
        if ($request->filled('auditable_id')) $query->where('auditable_id', $request->integer('auditable_id'));
        return response()->json($query->paginate(min(max($request->integer('per_page', 50), 1), 100)));
    }
}
