<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtaDeploymentCamera extends Model
{
    protected $table = 'deployment_cameras';

    protected $fillable = [
        'deployment_id',
        'camera_id',
        'old_version',
        'target_version',
        'status',
        'progress',
        'message',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function deployment()
    {
        return $this->belongsTo(OtaDeployment::class, 'deployment_id');
    }

    public function camera()
    {
        return $this->belongsTo(Camera::class, 'camera_id');
    }
}
