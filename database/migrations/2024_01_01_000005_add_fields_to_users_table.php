<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rôle : direction (accès global), coordinateur (accès par projet),
            // agent (accès terrain, app mobile uniquement)
            $table->string('role')->default('agent')->after('email');
            $table->foreignId('health_center_id')->nullable()->after('role')
                ->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->after('health_center_id')
                ->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('project_id');
            $table->boolean('is_active')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('health_center_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
    }
};
