<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->string('firmware')->nullable();
            $table->string('build')->nullable();
            $table->string('board')->nullable();
            $table->string('model')->nullable();
            $table->boolean('ota_supported')->default(false);
            $table->boolean('ota_running')->default(false);
            $table->unsignedBigInteger('free_ota_space')->nullable();
            $table->string('last_ota_result')->nullable();
            $table->timestamp('last_ota')->nullable();
            $table->uuid('current_deployment_id')->nullable();
            $table->integer('wifi_channel')->nullable();
            $table->string('wifi_bssid')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->dropColumn([
                'firmware',
                'build',
                'board',
                'model',
                'ota_supported',
                'ota_running',
                'free_ota_space',
                'last_ota_result',
                'last_ota',
                'current_deployment_id',
                'wifi_channel',
                'wifi_bssid'
            ]);
        });
    }
};
