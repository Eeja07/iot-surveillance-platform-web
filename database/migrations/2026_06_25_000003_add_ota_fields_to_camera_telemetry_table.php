<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {

            if (!Schema::hasColumn('cameras', 'assigned_profile_id')) {
                $table->foreignId('assigned_profile_id')
                    ->nullable()
                    ->constrained('camera_profiles')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('cameras', 'last_config_time')) {
                $table->timestamp('last_config_time')->nullable();
            }

            if (!Schema::hasColumn('cameras', 'last_sync')) {
                $table->timestamp('last_sync')->nullable();
            }

            if (!Schema::hasColumn('cameras', 'pending_changes')) {
                $table->json('pending_changes')->nullable();
            }

        });

        Schema::table('camera_telemetry', function (Blueprint $table) {

            if (!Schema::hasColumn('camera_telemetry', 'jpeg_quality')) {
                $table->integer('jpeg_quality')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'frame_size')) {
                $table->string('frame_size')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'capture_interval_ms')) {
                $table->integer('capture_interval_ms')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'telemetry_interval_ms')) {
                $table->integer('telemetry_interval_ms')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'mqtt_buffer')) {
                $table->integer('mqtt_buffer')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'image_enabled')) {
                $table->boolean('image_enabled')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'telemetry_enabled')) {
                $table->boolean('telemetry_enabled')->nullable();
            }

            if (!Schema::hasColumn('camera_telemetry', 'ota_enabled')) {
                $table->boolean('ota_enabled')->nullable();
            }

        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {

            if (Schema::hasColumn('cameras', 'assigned_profile_id')) {
                try {
                    $table->dropForeign(['assigned_profile_id']);
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn('assigned_profile_id');
            }

            foreach ([
                'last_config_time',
                'last_sync',
                'pending_changes',
            ] as $column) {
                if (Schema::hasColumn('cameras', $column)) {
                    $table->dropColumn($column);
                }
            }

        });

        Schema::table('camera_telemetry', function (Blueprint $table) {

            foreach ([
                'jpeg_quality',
                'frame_size',
                'capture_interval_ms',
                'telemetry_interval_ms',
                'mqtt_buffer',
                'image_enabled',
                'telemetry_enabled',
                'ota_enabled',
            ] as $column) {
                if (Schema::hasColumn('camera_telemetry', $column)) {
                    $table->dropColumn($column);
                }
            }

        });
    }
};
