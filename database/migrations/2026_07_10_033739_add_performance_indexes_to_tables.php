<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('image_records', function (Blueprint $table) {
            $table->index(['camera_id', 'captured_at']);
        });

        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->index(['camera_id', 'created_at']);
        });

        Schema::table('motion_events', function (Blueprint $table) {
            $table->index(['camera_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_records', function (Blueprint $table) {
            $table->dropIndex(['camera_id', 'captured_at']);
        });

        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->dropIndex(['camera_id', 'created_at']);
        });

        Schema::table('motion_events', function (Blueprint $table) {
            $table->dropIndex(['camera_id', 'created_at']);
        });
    }
};
