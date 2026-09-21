<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_email_logs', function (Blueprint $table) {
            $table->string('status')
                ->default('queued')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('document_email_logs', function (Blueprint $table) {
            $table->string('status')
                ->default('sent')
                ->change();
        });
    }
};
