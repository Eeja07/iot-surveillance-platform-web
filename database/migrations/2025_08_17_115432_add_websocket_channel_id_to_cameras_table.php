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
    Schema::table('cameras', function (Blueprint $table) {
      // Tambahkan kolom setelah 'api_key'
      // Dibuat nullable untuk kamera yang sudah ada sebelumnya
      $table->string('websocket_channel_id')->unique()->nullable()->after('api_key');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('cameras', function (Blueprint $table) {
      $table->dropColumn('websocket_channel_id');
    });
  }
};
