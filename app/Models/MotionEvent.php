<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotionEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'camera_id',
        'image_record_id',
        'motion_score',
        'person_confidence',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'motion_score' => 'float',
        'person_confidence' => 'float',
    ];

    /**
     * Get the camera that this motion event belongs to.
     */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    /**
     * Get the image record associated with this motion event.
     */
    public function imageRecord(): BelongsTo
    {
        return $this->belongsTo(ImageRecord::class);
    }
}
