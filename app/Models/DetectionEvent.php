<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetectionEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image_record_id',
        'object_class',
        'confidence',
        'x_min',
        'y_min',
        'x_max',
        'y_max',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'confidence' => 'float',
        'x_min'      => 'float',
        'y_min'      => 'float',
        'x_max'      => 'float',
        'y_max'      => 'float',
    ];

    /**
     * Get the image record associated with this detection event.
     */
    public function imageRecord(): BelongsTo
    {
        return $this->belongsTo(ImageRecord::class);
    }
}
