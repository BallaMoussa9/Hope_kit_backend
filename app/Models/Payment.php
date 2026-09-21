<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Payment extends Model {
 protected $fillable=['commercial_document_id','customer_id','recorded_by','amount','paid_at','method','status','reference','notes'];
 protected $casts=['amount'=>'decimal:2','paid_at'=>'datetime'];
 public function document():BelongsTo{return $this->belongsTo(CommercialDocument::class,'commercial_document_id');}
 public function customer():BelongsTo{return $this->belongsTo(Customer::class);}
 public function recorder():BelongsTo{return $this->belongsTo(User::class,'recorded_by');}
}
