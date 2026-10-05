<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->unsignedInteger('expected_delay_days')->nullable()->after('used_at');
        });
    }

    public function down(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->dropColumn('expected_delay_days');
        });
    }
};
