<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('suppliers', function(Blueprint $t){$t->id();$t->string('name');$t->string('contact_name')->nullable();$t->string('phone')->nullable();$t->string('email')->nullable();$t->text('address')->nullable();$t->string('tax_number')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();}); } public function down(): void {Schema::dropIfExists('suppliers');}};
