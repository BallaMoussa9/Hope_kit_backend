<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: "Community Health Program – Fekola"
            $table->string('partner')->nullable(); // ex: "B2Gold Corp."
            $table->text('description')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Table pivot : un projet peut couvrir plusieurs centres de santé
        Schema::create('project_health_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('health_center_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'health_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_health_center');
        Schema::dropIfExists('projects');
    }
};
