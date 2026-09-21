<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('sales_orders', function(Blueprint $t){
   $t->id(); $t->string('order_number')->unique(); $t->foreignId('customer_id')->constrained()->restrictOnDelete();
   $t->enum('status',['draft','validated','processing','partially_delivered','delivered','cancelled'])->default('draft');
   $t->date('order_date'); $t->date('expected_date')->nullable(); $t->decimal('discount',12,2)->default(0); $t->decimal('subtotal',12,2)->default(0); $t->decimal('total_amount',12,2)->default(0); $t->string('currency',3)->default('XOF'); $t->text('notes')->nullable(); $t->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete(); $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps(); $t->index(['status','order_date']);
  });
  Schema::create('sales_order_lines', function(Blueprint $t){$t->id();$t->foreignId('sales_order_id')->constrained()->cascadeOnDelete();$t->foreignId('inventory_product_id')->constrained()->restrictOnDelete();$t->unsignedInteger('quantity');$t->unsignedInteger('delivered_quantity')->default(0);$t->decimal('unit_price',12,2);$t->decimal('discount',12,2)->default(0);$t->decimal('line_total',12,2);$t->timestamps();});
  Schema::table('commercial_documents', function(Blueprint $t){$t->foreignId('sales_order_id')->nullable()->after('customer_id')->constrained('sales_orders')->nullOnDelete();});
 }
 public function down(): void {Schema::table('commercial_documents',fn(Blueprint $t)=>$t->dropConstrainedForeignId('sales_order_id'));Schema::dropIfExists('sales_order_lines');Schema::dropIfExists('sales_orders');}
};
