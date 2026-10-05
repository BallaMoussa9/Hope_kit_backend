<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ivr_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();

            $table->enum('call_type', [
                'cpn_reminder',      // rappel consultation prénatale
                'delivery_reminder', // rappel proche de la DPA
                'custom',
            ])->default('cpn_reminder');

            $table->timestamp('scheduled_at');
            $table->unsignedTinyInteger('attempt_count')->default(0);

            $table->enum('status', [
                'pending', 'sent', 'answered', 'no_answer', 'failed', 'cancelled',
            ])->default('pending');

            $table->timestamp('last_attempt_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ivr_calls');
    }
};
