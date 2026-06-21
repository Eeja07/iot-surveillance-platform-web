<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CameraGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * Relasi ke User pemilik grup.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke banyak kamera yang ada di dalam grup ini.
     * Menggunakan 'group_id' sebagai foreign key di tabel cameras.
     */
    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class, 'group_id');
    }
}
