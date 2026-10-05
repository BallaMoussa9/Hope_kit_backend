<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('customers', function(Blueprint $t){$t->string('type')->default('individual')->after('id');}); Schema::table('inventory_products', function(Blueprint $t){$t->string('category')->nullable()->after('name');$t->unsignedInteger('stock_max')->nullable()->after('stock_min');}); }
 public function down(): void {Schema::table('inventory_products',fn(Blueprint $t)=>$t->dropColumn(['category','stock_max']));Schema::table('customers',fn(Blueprint $t)=>$t->dropColumn('type'));}};
