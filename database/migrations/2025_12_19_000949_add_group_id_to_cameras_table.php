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
        // Menambahkan kolom group_id sebagai Foreign Key
        if (!Schema::hasColumn('cameras', 'group_id')) {
            $table->foreignId('group_id')
                  ->nullable()
                  ->after('user_id')
                  ->constrained('camera_groups')
                  ->onDelete('set null');
        }
    });
}

public function down(): void
{
    Schema::table('cameras', function (Blueprint $table) {
        $table->dropForeign(['group_id']);
        $table->dropColumn('group_id');
    });
}
};
