<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->foreignId('assigned_profile_id')->nullable()->constrained('camera_profiles')->onDelete('set null');
            $table->timestamp('last_config_time')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->json('pending_changes')->nullable();
        });

        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->integer('jpeg_quality')->nullable();
            $table->string('frame_size')->nullable();
            $table->integer('capture_interval_ms')->nullable();
            $table->integer('telemetry_interval_ms')->nullable();
            $table->integer('mqtt_buffer')->nullable();
            $table->boolean('image_enabled')->nullable();
            $table->boolean('telemetry_enabled')->nullable();
            $table->boolean('ota_enabled')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropForeign(['assigned_profile_id']);
            $table->dropColumn(['assigned_profile_id', 'last_config_time', 'last_sync', 'pending_changes']);
        });

        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->dropColumn([
                'jpeg_quality',
                'frame_size',
                'capture_interval_ms',
                'telemetry_interval_ms',
                'mqtt_buffer',
                'image_enabled',
                'telemetry_enabled',
                'ota_enabled',
            ]);
        });
    }
};
