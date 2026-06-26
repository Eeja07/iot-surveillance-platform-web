<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('jpeg_quality');
            $table->string('frame_size');
            $table->integer('capture_interval_ms');
            $table->integer('telemetry_interval_ms');
            $table->integer('mqtt_buffer');
            $table->boolean('image_enabled')->default(true);
            $table->boolean('telemetry_enabled')->default(true);
            $table->boolean('ota_enabled')->default(true);
            $table->timestamps();
        });

        // Seed default profiles
        DB::table('camera_profiles')->insert([
            [
                'name' => 'Low Bandwidth',
                'jpeg_quality' => 35,
                'frame_size' => 'QVGA',
                'capture_interval_ms' => 10000,
                'telemetry_interval_ms' => 60000,
                'mqtt_buffer' => 5,
                'image_enabled' => true,
                'telemetry_enabled' => true,
                'ota_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Balanced',
                'jpeg_quality' => 15,
                'frame_size' => 'VGA',
                'capture_interval_ms' => 5000,
                'telemetry_interval_ms' => 30000,
                'mqtt_buffer' => 15,
                'image_enabled' => true,
                'telemetry_enabled' => true,
                'ota_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'High Quality',
                'jpeg_quality' => 10,
                'frame_size' => 'SVGA',
                'capture_interval_ms' => 2000,
                'telemetry_interval_ms' => 15000,
                'mqtt_buffer' => 30,
                'image_enabled' => true,
                'telemetry_enabled' => true,
                'ota_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Custom',
                'jpeg_quality' => 12,
                'frame_size' => 'VGA',
                'capture_interval_ms' => 5000,
                'telemetry_interval_ms' => 30000,
                'mqtt_buffer' => 10,
                'image_enabled' => true,
                'telemetry_enabled' => true,
                'ota_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_profiles');
    }
};
