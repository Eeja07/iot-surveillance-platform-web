<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationHistory extends Model
{
    public $timestamps = false; // We use created_at only

    protected $fillable = [
        'camera_id',
        'user_id',
        'old_config',
        'new_config',
        'changed_fields',
        'status',
        'message',
        'created_at',
    ];

    protected $casts = [
        'old_config' => 'json',
        'new_config' => 'json',
        'changed_fields' => 'json',
        'created_at' => 'datetime',
    ];

    public function camera()
    {
        return $this->belongsTo(Camera::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
