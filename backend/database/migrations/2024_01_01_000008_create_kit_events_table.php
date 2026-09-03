<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kit_events', function (Blueprint $table) {
            $table->id();

            // UUID généré côté mobile au moment du scan — permet à l'app hors-ligne
            // de renvoyer plusieurs fois le même événement sans le dupliquer une
            // fois la connexion revenue (idempotence de la synchronisation).
            $table->uuid('client_uuid')->unique();

            $table->foreignId('kit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('health_center_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->enum('event_type', [
                'received',      // réception au centre
                'distributed',   // remise à la bénéficiaire
                'used',          // confirmation d'utilisation
                'not_used',      // confirmation de non-utilisation
            ]);

            // Données libres du formulaire au moment du scan (motif, lieu
            // d'accouchement, etc.) — garde une trace brute même si le
            // schéma des tables principales évolue plus tard.
            $table->json('payload')->nullable();

            $table->timestamp('occurred_at'); // heure réelle du scan sur le téléphone (hors-ligne)
            $table->timestamp('synced_at')->nullable(); // heure d'arrivée sur le serveur

            $table->timestamps();

            $table->index(['kit_id', 'event_type']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kit_events');
    }
};
