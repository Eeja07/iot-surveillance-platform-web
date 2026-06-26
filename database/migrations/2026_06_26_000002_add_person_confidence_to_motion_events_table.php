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
        Schema::table('motion_events', function (Blueprint $table) {
            $table->decimal('person_confidence', 5, 4)->nullable()->after('motion_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motion_events', function (Blueprint $table) {
            $table->dropColumn('person_confidence');
        });
    }
};
