<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtaHistory extends Model
{
    use HasFactory;

    protected $table = 'ota_history';

    protected $fillable = [
        'camera_id',
        'version',
        'deployment_id',
        'started_at',
        'finished_at',
        'status',
        'message',
        'progress',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'progress' => 'integer',
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }
}
