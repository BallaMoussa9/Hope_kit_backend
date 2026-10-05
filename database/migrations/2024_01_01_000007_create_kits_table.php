<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kits', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code')->unique(); // valeur encodée dans le QR Code
            $table->string('batch_number')->nullable(); // lot de production
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();

            // Statut courant du kit — mis à jour à chaque événement (voir kit_events)
            $table->enum('status', [
                'created',      // fabriqué / enregistré, pas encore reçu par un centre
                'in_stock',     // reçu par un centre de santé
                'distributed',  // remis à une bénéficiaire
                'used',         // confirmé utilisé lors de l'accouchement
                'not_used',     // confirmé non utilisé (péremption, perte, autre)
            ])->default('created');

            $table->foreignId('current_health_center_id')->nullable()
                ->constrained('health_centers')->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->timestamp('received_at')->nullable();    // arrivée au centre
            $table->timestamp('distributed_at')->nullable(); // remise à la bénéficiaire
            $table->timestamp('used_at')->nullable();        // confirmation d'utilisation

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kits');
    }
};
