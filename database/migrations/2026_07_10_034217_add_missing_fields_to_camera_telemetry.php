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
        Schema::table('camera_telemetry', function (Blueprint $table) {
            if (!Schema::hasColumn('camera_telemetry', 'firmware')) {
                $table->string('firmware')->nullable();
            }
            if (!Schema::hasColumn('camera_telemetry', 'build')) {
                $table->string('build')->nullable();
            }
            if (!Schema::hasColumn('camera_telemetry', 'board')) {
                $table->string('board')->nullable();
            }
            if (!Schema::hasColumn('camera_telemetry', 'model')) {
                $table->string('model')->nullable();
            }
            if (!Schema::hasColumn('camera_telemetry', 'ota_supported')) {
                $table->boolean('ota_supported')->default(false);
            }
            if (!Schema::hasColumn('camera_telemetry', 'ota_running')) {
                $table->boolean('ota_running')->default(false);
            }
            if (!Schema::hasColumn('camera_telemetry', 'free_ota_space')) {
                $table->bigInteger('free_ota_space')->nullable();
            }
            if (!Schema::hasColumn('camera_telemetry', 'wifi_channel')) {
                $table->integer('wifi_channel')->nullable();
            }
            if (!Schema::hasColumn('camera_telemetry', 'wifi_bssid')) {
                $table->string('wifi_bssid')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('camera_telemetry', function (Blueprint $table) {
            foreach ([
                'firmware',
                'build',
                'board',
                'model',
                'ota_supported',
                'ota_running',
                'free_ota_space',
                'wifi_channel',
                'wifi_bssid'
            ] as $column) {
                if (Schema::hasColumn('camera_telemetry', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
