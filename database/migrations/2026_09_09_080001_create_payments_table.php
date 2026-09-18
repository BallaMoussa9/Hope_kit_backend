<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('payments', function(Blueprint $t){$t->id();$t->foreignId('commercial_document_id')->constrained()->cascadeOnDelete();$t->foreignId('customer_id')->constrained()->restrictOnDelete();$t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();$t->decimal('amount',12,2);$t->dateTime('paid_at');$t->string('method')->default('orange_money');$t->string('reference')->nullable();$t->text('notes')->nullable();$t->timestamps();$t->index(['commercial_document_id','paid_at']);}); }
 public function down(): void {Schema::dropIfExists('payments');}};
