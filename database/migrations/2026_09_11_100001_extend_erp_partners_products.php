<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
 Schema::table('customers',function(Blueprint $t){$t->string('payment_terms')->nullable();$t->decimal('credit_limit',12,2)->default(0);$t->string('city')->nullable();$t->string('country')->nullable();$t->text('notes')->nullable();});
 Schema::table('suppliers',function(Blueprint $t){$t->string('supplier_code')->nullable()->unique();$t->string('payment_terms')->nullable();$t->string('city')->nullable();$t->string('country')->nullable();$t->text('notes')->nullable();});
 Schema::table('inventory_products',function(Blueprint $t){$t->string('barcode')->nullable()->unique();$t->string('location')->nullable();$t->boolean('track_stock')->default(true);});
 } public function down(): void {Schema::table('inventory_products',fn(Blueprint $t)=>$t->dropColumn(['barcode','location','track_stock']));Schema::table('suppliers',fn(Blueprint $t)=>$t->dropColumn(['supplier_code','payment_terms','city','country','notes']));Schema::table('customers',fn(Blueprint $t)=>$t->dropColumn(['payment_terms','credit_limit','city','country','notes']));}};
