<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            $table->enum('type', [
                'low_stock',              // stock faible dans un centre
                'stale_distribution',     // kit distribué depuis trop longtemps sans confirmation
            ]);

            $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');

            $table->foreignId('health_center_id')->nullable()
                ->constrained()->cascadeOnDelete();
            $table->foreignId('kit_id')->nullable()
                ->constrained()->cascadeOnDelete();

            $table->string('message');

            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Évite de recréer la même alerte à chaque exécution du
            // détecteur tant qu'elle n'est pas résolue.
            $table->unique(['type', 'health_center_id', 'kit_id', 'status'], 'alerts_dedup_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
