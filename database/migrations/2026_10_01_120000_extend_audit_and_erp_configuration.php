<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('audit_logs', function(Blueprint $t) { $t->string('actor_name')->nullable(); $t->string('actor_email')->nullable(); $t->index(['user_id','created_at']); $t->index(['action','created_at']); });
  Schema::create('erp_settings', function(Blueprint $t) { $t->id(); $t->string('key')->unique(); $t->json('value'); $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps(); });
  DB::table('erp_settings')->insert(['key'=>'currency','value'=>json_encode(['code'=>'XOF','symbol'=>'FCFA','label'=>'Franc CFA BCEAO','decimal_places'=>0]),'created_at'=>now(),'updated_at'=>now()]);
  Schema::table('sales_orders', function(Blueprint $t) { $t->string('payment_terms',24)->default('before_delivery'); $t->decimal('pre_delivery_amount',14,2)->default(0); $t->unsignedSmallInteger('due_days')->nullable(); $t->date('due_date')->nullable(); $t->index(['payment_terms','status']); });
  Schema::table('payments', function(Blueprint $t) { $t->string('currency',3)->default('XOF'); $t->string('reversal_reason')->nullable(); $t->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamp('reversed_at')->nullable(); });
 }
 public function down(): void {
  Schema::table('payments', function(Blueprint $t) { $t->dropConstrainedForeignId('reversed_by'); $t->dropColumn(['currency','reversal_reason','reversed_at']); });
  Schema::table('sales_orders', function(Blueprint $t) { $t->dropIndex(['payment_terms','status']); $t->dropColumn(['payment_terms','pre_delivery_amount','due_days','due_date']); });
  Schema::dropIfExists('erp_settings');
  Schema::table('audit_logs', function(Blueprint $t) { $t->dropIndex(['user_id','created_at']); $t->dropIndex(['action','created_at']); $t->dropColumn(['actor_name','actor_email']); });
 }
};
