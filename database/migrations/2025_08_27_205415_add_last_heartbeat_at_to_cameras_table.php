<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_last_heartbeat_at_to_cameras_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            // Tambahkan kolom ini setelah kolom 'is_active' atau di akhir
            $table->timestamp('last_heartbeat_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn('last_heartbeat_at');
        });
    }
};
