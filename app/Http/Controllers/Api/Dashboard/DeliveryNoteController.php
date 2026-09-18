<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use App\Models\WarehouseStock;
use App\Models\StockMovement;
use App\Models\InventoryProduct;
use App\Services\DocumentNumberService;
use App\Services\SimplePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DeliveryNoteController extends Controller
{
    public function index(Request $r)
    {
        return response()->json(DeliveryNote::with(['order.customer','warehouse','lines.product'])->latest()->paginate($r->integer('per_page',25)));
    }

    public function store(Request $r, DocumentNumberService $numbers, SalesOrder $order)
    {
        $v=$r->validate(['lines'=>'required|array|min:1','lines.*.inventory_product_id'=>'required|exists:inventory_products,id','lines.*.quantity'=>'required|integer|min:1','notes'=>'nullable|string']);
        return DB::transaction(function() use($v,$numbers,$order,$r){
            abort_unless(in_array($order->status,['validated','processing','partially_delivered'],true),422,'La commande doit être validée et prête à être livrée.');
            $order->load('lines');
            $delivery=DeliveryNote::create(['delivery_number'=>$numbers->next('delivery','BL'),'sales_order_id'=>$order->id,'warehouse_id'=>$order->warehouse_id,'status'=>'draft','delivery_date'=>now()->toDateString(),'notes'=>$v['notes']??null,'created_by'=>$r->user()->id]);
            $requested = array_filter($v['lines'], fn($x) => (int)$x['quantity'] > 0);
            abort_if(!$requested,422,'Saisissez au moins une quantité à livrer.');
            foreach($requested as $x){
                $line=$order->lines->firstWhere('inventory_product_id',(int)$x['inventory_product_id']);
                abort_unless($line,422,'Produit absent de la commande.');
                $qty=(int)$x['quantity']; $remaining=(int)$line->quantity-(int)$line->delivered_quantity;
                abort_if($qty>$remaining,422,'Quantité livrée supérieure au reste de la commande.');
                $product=InventoryProduct::lockForUpdate()->findOrFail($line->inventory_product_id);
                if($order->warehouse_id){
                    $ws=WarehouseStock::lockForUpdate()->where(['warehouse_id'=>$order->warehouse_id,'inventory_product_id'=>$product->id])->first();
                    abort_unless($ws && ((int)$ws->quantity-(int)$ws->reserved_quantity)>=$qty,422,'Stock disponible insuffisant dans l’entrepôt.');
                    $before=(int)$ws->quantity;
                    $ws->update(['quantity'=>$before-$qty,'reserved_quantity'=>max(0,(int)$ws->reserved_quantity-$qty)]);
                } else {
                    abort_if((int)$product->stock_quantity<$qty,422,'Stock global insuffisant pour '.$product->name);
                }
                $globalBefore=(int)$product->stock_quantity;
                abort_if($globalBefore<$qty,422,'Stock global insuffisant pour '.$product->name);
                $product->update(['stock_quantity'=>$globalBefore-$qty]);
                StockMovement::create(['inventory_product_id'=>$product->id,'warehouse_id'=>$order->warehouse_id,'type'=>'exit','quantity'=>$qty,'stock_before'=>$globalBefore,'stock_after'=>$globalBefore-$qty,'unit_price'=>$line->unit_price,'reference'=>$delivery->delivery_number,'reason'=>'Livraison client','user_id'=>$r->user()->id]);
                $delivery->lines()->create(['inventory_product_id'=>$product->id,'quantity'=>$qty]);
                $line->increment('delivered_quantity',$qty);
            }
            $complete=!$order->lines()->whereColumn('delivered_quantity','<','quantity')->exists();
            $order->update(['status'=>$complete?'delivered':'partially_delivered']);
            $delivery->update(['status'=>'delivered']);
            return response()->json($delivery->fresh(['order.customer','warehouse','lines.product']),201);
        });
    }

    public function show(DeliveryNote $delivery){return response()->json($delivery->load(['order.customer','warehouse','lines.product']));}

    public function pdf(DeliveryNote $delivery, SimplePdfService $pdf){
        $delivery->load(['order.customer','warehouse','lines.product']);
        $lines=['Bon de livraison : '.$delivery->delivery_number,'Commande : '.($delivery->order->order_number??'—'),'Client : '.($delivery->order->customer->name??'—'),'Date : '.$delivery->delivery_date,'Entrepôt : '.($delivery->warehouse->name??'—'),''];
        foreach($delivery->lines as $l)$lines[]=($l->product->name??'Produit').' | Quantité : '.$l->quantity;
        $lines[]='';$lines[]='Statut : '.($delivery->status??'—');
        return response($pdf->make('BON DE LIVRAISON', $lines),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'inline; filename="'.$delivery->delivery_number.'.pdf"','Cache-Control'=>'no-store']);
    }

    public function email(Request $request, DeliveryNote $delivery, SimplePdfService $pdf){
        $to=$request->validate(['email'=>'nullable|email'])['email']??$delivery->order?->customer?->email;
        abort_unless($to,422,'Le client n’a pas d’adresse email.');
        if(config('mail.default')==='log')abort(422,'SMTP non configuré. Configurez les paramètres MAIL_* dans .env.');
        $delivery->load(['order.customer','lines.product']);
        $lines=['Bon de livraison : '.$delivery->delivery_number,'Commande : '.($delivery->order->order_number??'—'),'Client : '.($delivery->order->customer->name??'—'),'Date : '.$delivery->delivery_date,''];foreach($delivery->lines as $l)$lines[]=($l->product->name??'Produit').' | Quantité : '.$l->quantity;
        $bytes=$pdf->make('BON DE LIVRAISON',$lines);
        try{Mail::send('emails.document-available',['title'=>'Votre bon de livraison HOPE '.$delivery->delivery_number,'document'=>$delivery],function($m)use($to,$bytes,$delivery){$m->to($to)->subject('HOPE - Bon de livraison '.$delivery->delivery_number)->attachData($bytes,$delivery->delivery_number.'.pdf',['mime'=>'application/pdf']);});\App\Models\DocumentEmailLog::create(['sales_order_id'=>$delivery->sales_order_id,'recipient'=>$to,'subject'=>'HOPE - Bon de livraison '.$delivery->delivery_number,'status'=>'sent','sent_by'=>$request->user()->id]);return response()->json(['message'=>'Bon de livraison envoyé avec succès à '.$to.'.']);}catch(\Throwable $e){report($e);\App\Models\DocumentEmailLog::create(['sales_order_id'=>$delivery->sales_order_id,'recipient'=>$to,'subject'=>'HOPE - Bon de livraison '.$delivery->delivery_number,'status'=>'failed','error'=>app()->isLocal()?$e->getMessage():'Erreur SMTP','sent_by'=>$request->user()->id]);return response()->json(['message'=>'Échec de l’envoi email. Vérifiez la configuration SMTP.','error'=>app()->isLocal()?$e->getMessage():null],500);}
    }
}
