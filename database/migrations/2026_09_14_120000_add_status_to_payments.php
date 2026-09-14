<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('payments', function (Blueprint $t) { $t->enum('status',['recorded','validated','cancelled'])->default('recorded')->after('method'); $t->index(['status','paid_at']); }); }
    public function down(): void { Schema::table('payments', function (Blueprint $t) { $t->dropIndex(['status','paid_at']); $t->dropColumn('status'); }); }
};
