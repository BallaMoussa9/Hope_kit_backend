<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WarehouseStock extends Model {protected $fillable=['warehouse_id','inventory_product_id','quantity','reserved_quantity']; public function warehouse():BelongsTo{return $this->belongsTo(Warehouse::class);} public function product():BelongsTo{return $this->belongsTo(InventoryProduct::class,'inventory_product_id');} }
