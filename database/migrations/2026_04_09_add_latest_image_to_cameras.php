<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->string('latest_image_path')->nullable()->after('last_heartbeat_at');
            $table->timestamp('latest_image_at')->nullable()->after('latest_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn(['latest_image_path', 'latest_image_at']);
        });
    }
};
