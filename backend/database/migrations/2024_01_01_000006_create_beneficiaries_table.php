<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->enum('preferred_language', [
                'bambara', 'peulh', 'soninke', 'francais', 'autre',
            ])->default('bambara');

            $table->foreignId('health_center_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->foreignId('registered_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();

            $table->enum('delivery_location', ['domicile', 'centre_de_sante', 'autre'])
                ->nullable();

            // Consentement pour les appels automatiques (IVR) — exigence RGPD/confidentialité
            $table->boolean('ivr_consent')->default(true);

            $table->timestamps();

            $table->index('phone');
            $table->index('expected_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
