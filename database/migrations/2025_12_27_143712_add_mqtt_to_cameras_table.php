<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('cameras', function (Blueprint $table) {
        // Tambahkan kolom MQTT setelah api_key
        $table->string('mqtt_username')->unique()->after('api_key')->nullable();
        $table->string('mqtt_password')->after('mqtt_username')->nullable();
        $table->enum('mqtt_status', ['online', 'offline'])->default('offline')->after('is_active');

        // Pastikan indexing untuk performa webhook
        $table->index('device_id');
    });

    Schema::create('firmwares', function (Blueprint $table) {
        $table->id();
        $table->string('version');
        $table->string('file_path');
        $table->string('checksum'); // MD5/SHA256
        $table->text('changelog')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            //
        });
    }
};
