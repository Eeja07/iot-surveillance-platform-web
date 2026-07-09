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
        if (Schema::hasTable('camera_telemetry')) {
            return;
        }

        Schema::create('camera_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');
            $table->string('device_id');
            $table->integer('rssi')->nullable();
            $table->integer('free_heap')->nullable();
            $table->integer('uptime_sec')->nullable();
            $table->boolean('mqtt_connected')->default(false);
            $table->boolean('ws_connected')->default(false);
            $table->integer('mqtt_reconnect')->default(0);
            $table->integer('ws_close_count')->default(0);
            $table->integer('publish_fail')->default(0);
            $table->integer('publish_ms')->default(0);
            $table->timestamp('last_ota')->nullable();
            $table->text('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_telemetry');
    }
};
