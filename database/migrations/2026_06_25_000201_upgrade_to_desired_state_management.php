<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camera_profiles', function (Blueprint $table) {
            $table->integer('config_version')->default(1);
            $table->string('config_hash')->nullable();
            $table->boolean('restart_required')->default(false);
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->json('desired_config')->nullable();
            $table->json('current_config')->nullable();
            $table->integer('desired_config_version')->default(1);
            $table->integer('current_config_version')->default(0);
            $table->string('desired_config_hash')->nullable();
            $table->string('current_config_hash')->nullable();
            $table->string('last_config_status')->default('Applied'); // Pending, Queued, Sending, Applied, Rejected, Expired, Failed, Timeout, Cancelled
            $table->text('last_failure_message')->nullable();
            $table->timestamp('last_applied_at')->nullable();
        });

        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->integer('config_version')->nullable();
            $table->string('config_hash')->nullable();
        });

        Schema::table('configuration_histories', function (Blueprint $table) {
            $table->integer('config_version')->nullable();
            $table->string('config_hash')->nullable();
            $table->string('rollback_from')->nullable();
            $table->string('rollback_to')->nullable();
        });

        // Compute hashes for existing profiles
        $profiles = DB::table('camera_profiles')->get();
        foreach ($profiles as $profile) {
            $config = [
                'jpeg_quality' => $profile->jpeg_quality,
                'frame_size' => $profile->frame_size,
                'capture_interval_ms' => $profile->capture_interval_ms,
                'telemetry_interval_ms' => $profile->telemetry_interval_ms,
                'mqtt_buffer' => $profile->mqtt_buffer,
                'image_enabled' => $profile->image_enabled ? 1 : 0,
                'telemetry_enabled' => $profile->telemetry_enabled ? 1 : 0,
                'ota_enabled' => $profile->ota_enabled ? 1 : 0,
            ];
            ksort($config);
            $hash = hash('sha256', json_encode($config));
            DB::table('camera_profiles')->where('id', $profile->id)->update([
                'config_hash' => $hash
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('camera_profiles', function (Blueprint $table) {
            $table->dropColumn(['config_version', 'config_hash', 'restart_required']);
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn([
                'desired_config',
                'current_config',
                'desired_config_version',
                'current_config_version',
                'desired_config_hash',
                'current_config_hash',
                'last_config_status',
                'last_failure_message',
                'last_applied_at',
            ]);
        });

        Schema::table('camera_telemetry', function (Blueprint $table) {
            $table->dropColumn(['config_version', 'config_hash']);
        });

        Schema::table('configuration_histories', function (Blueprint $table) {
            $table->dropColumn(['config_version', 'config_hash', 'rollback_from', 'rollback_to']);
        });
    }
};
