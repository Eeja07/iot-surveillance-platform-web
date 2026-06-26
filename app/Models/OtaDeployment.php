<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OtaDeployment extends Model
{
    protected $table = 'ota_deployments';
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'firmware_id',
        'created_by',
        'status',
        'started_at',
        'finished_at',
        'scheduled_at',
        'rollout_percentage',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function firmware()
    {
        return $this->belongsTo(OtaFirmware::class, 'firmware_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deploymentCameras()
    {
        return $this->hasMany(OtaDeploymentCamera::class, 'deployment_id');
    }

    public function cameras()
    {
        return $this->belongsToMany(Camera::class, 'deployment_cameras', 'deployment_id', 'camera_id');
    }
}
