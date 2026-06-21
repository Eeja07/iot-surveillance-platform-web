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
        // Tambahkan kolom jika belum ada
        if (!Schema::hasColumn('camera_groups', 'user_id')) {
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
        }
        if (!Schema::hasColumn('camera_groups', 'name')) {
            $table->string('name')->after('user_id');
        }
    });
}

public function down(): void
{
    Schema::table('camera_groups', function (Blueprint $table) {
        $table->dropColumn(['user_id', 'name']);
    });
}
};
