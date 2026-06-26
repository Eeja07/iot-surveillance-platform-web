<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CameraProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'jpeg_quality',
        'frame_size',
        'capture_interval_ms',
        'telemetry_interval_ms',
        'mqtt_buffer',
        'image_enabled',
        'telemetry_enabled',
        'ota_enabled',
        'config_version',
        'config_hash',
        'restart_required',
    ];

    protected $casts = [
        'image_enabled' => 'boolean',
        'telemetry_enabled' => 'boolean',
        'ota_enabled' => 'boolean',
        'restart_required' => 'boolean',
        'config_version' => 'integer',
    ];

    public function cameras()
    {
        return $this->hasMany(Camera::class, 'assigned_profile_id');
    }

    protected static function booted()
    {
        static::saving(function ($profile) {
            $config = [
                'jpeg_quality' => (int)$profile->jpeg_quality,
                'frame_size' => (string)$profile->frame_size,
                'capture_interval_ms' => (int)$profile->capture_interval_ms,
                'telemetry_interval_ms' => (int)$profile->telemetry_interval_ms,
                'mqtt_buffer' => (int)$profile->mqtt_buffer,
                'image_enabled' => $profile->image_enabled ? 1 : 0,
                'telemetry_enabled' => $profile->telemetry_enabled ? 1 : 0,
                'ota_enabled' => $profile->ota_enabled ? 1 : 0,
            ];
            ksort($config);
            $newHash = hash('sha256', json_encode($config));

            if ($profile->exists) {
                $dirtyFields = ['jpeg_quality', 'frame_size', 'capture_interval_ms', 'telemetry_interval_ms', 'mqtt_buffer', 'image_enabled', 'telemetry_enabled', 'ota_enabled'];
                $changed = false;
                foreach ($dirtyFields as $field) {
                    if ($profile->isDirty($field)) {
                        $changed = true;
                        break;
                    }
                }
                if ($changed) {
                    $profile->config_version = ((int)$profile->getOriginal('config_version') ?: 1) + 1;
                }
            } else {
                if (empty($profile->config_version)) {
                    $profile->config_version = 1;
                }
            }

            $profile->config_hash = $newHash;
        });
    }
}
