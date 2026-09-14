<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SalesOrderLine extends Model { protected $fillable=['sales_order_id','inventory_product_id','quantity','delivered_quantity','unit_price','discount','line_total']; protected $casts=['unit_price'=>'decimal:2','discount'=>'decimal:2','line_total'=>'decimal:2']; public function order():BelongsTo{return $this->belongsTo(SalesOrder::class,'sales_order_id');} public function product():BelongsTo{return $this->belongsTo(InventoryProduct::class,'inventory_product_id');} }
