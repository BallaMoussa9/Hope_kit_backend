<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('audit_logs', function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('action');$t->string('auditable_type')->nullable();$t->unsignedBigInteger('auditable_id')->nullable();$t->json('old_values')->nullable();$t->json('new_values')->nullable();$t->string('ip_address',45)->nullable();$t->text('user_agent')->nullable();$t->timestamps();$t->index(['auditable_type','auditable_id']);}); }
 public function down(): void {Schema::dropIfExists('audit_logs');}};
