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
    Schema::table('camera_groups', function (Blueprint $table) {
        // Cek dulu apakah kolom sudah ada untuk menghindari error ganda
        if (!Schema::hasColumn('camera_groups', 'user_id')) {
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
        }
    });
}

public function down(): void
{
    Schema::table('camera_groups', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
    });
}
};
