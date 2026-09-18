<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('warehouses', function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('name');$t->text('address')->nullable();$t->string('phone')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
 Schema::create('warehouse_stocks', function(Blueprint $t){$t->id();$t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();$t->foreignId('inventory_product_id')->constrained()->cascadeOnDelete();$t->unsignedInteger('quantity')->default(0);$t->unsignedInteger('reserved_quantity')->default(0);$t->timestamps();$t->unique(['warehouse_id','inventory_product_id']);}); }
 public function down(): void {Schema::dropIfExists('warehouse_stocks');Schema::dropIfExists('warehouses');}
};
