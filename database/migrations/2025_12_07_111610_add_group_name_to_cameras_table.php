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
            // Tambahkan kolom 'group_name' (string) yang bisa bernilai NULL
            // Kami tempatkan setelah kolom 'name' untuk kerapian
            $table->string('group_name')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            // Hapus kolom 'group_name' jika migrasi di-rollback
            $table->dropColumn('group_name');
        });
    }
};
