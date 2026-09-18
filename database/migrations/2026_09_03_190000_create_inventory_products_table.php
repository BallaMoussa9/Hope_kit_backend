<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_products', function (Blueprint $table) {
            $table->id(); $table->string('sku')->unique(); $table->string('name');
            $table->string('unit')->default('kit'); $table->decimal('purchase_price',12,2)->default(0);
            $table->decimal('sale_price',12,2)->default(0); $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('stock_min')->default(0); $table->string('currency',3)->default('XOF');
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->index(['name','is_active']);
        });
        DB::table('inventory_products')->insert(['sku'=>'KIT-NENE','name'=>'Kit Néné','unit'=>'kit','purchase_price'=>0,'sale_price'=>0,'stock_quantity'=>0,'stock_min'=>10,'currency'=>'XOF','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function down(): void { Schema::dropIfExists('inventory_products'); }
};
