<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['distribution', 'usage', 'performance_par_centre']);
            $table->string('title');
            $table->json('filters')->nullable(); // période, région/district/centre utilisés
            $table->string('format')->default('csv'); // csv pour l'instant, pdf plus tard
            $table->string('file_path')->nullable();
            $table->unsignedInteger('row_count')->default(0);

            $table->foreignId('generated_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
