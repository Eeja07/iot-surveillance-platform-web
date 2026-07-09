<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('camera_profiles')->where('name', 'Low Bandwidth')->update(['mqtt_buffer' => 2048]);
        DB::table('camera_profiles')->where('name', 'Balanced')->update(['mqtt_buffer' => 4096]);
        DB::table('camera_profiles')->where('name', 'High Quality')->update(['mqtt_buffer' => 8192]);
        DB::table('camera_profiles')->where('name', 'Custom')->update(['mqtt_buffer' => 4096]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('camera_profiles')->where('name', 'Low Bandwidth')->update(['mqtt_buffer' => 5]);
        DB::table('camera_profiles')->where('name', 'Balanced')->update(['mqtt_buffer' => 15]);
        DB::table('camera_profiles')->where('name', 'High Quality')->update(['mqtt_buffer' => 30]);
        DB::table('camera_profiles')->where('name', 'Custom')->update(['mqtt_buffer' => 10]);
    }
};
