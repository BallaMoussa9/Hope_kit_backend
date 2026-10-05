<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ivr_voice_messages')) {
            return;
        }

        Schema::create('ivr_voice_messages', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('language', 32)->index();
            $table->string('call_type', 40)->index();
            $table->text('description')->nullable();
            $table->string('audio_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->index(['language', 'call_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ivr_voice_messages');
    }
};
