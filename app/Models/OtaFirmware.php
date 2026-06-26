<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtaFirmware extends Model
{
    use HasFactory;

    protected $table = 'ota_firmwares';

    protected $fillable = [
        'version',
        'board',
        'model',
        'build',
        'min_version',
        'mandatory',
        'rollback_allowed',
        'force',
        'size',
        'sha256',
        'url',
        'path',
        'release_notes',
        'uploaded_by',
        'download_count',
        'deploy_count',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
        'rollback_allowed' => 'boolean',
        'force' => 'boolean',
        'size' => 'integer',
        'download_count' => 'integer',
        'deploy_count' => 'integer',
    ];

    public function getFormattedSizeAttribute()
    {
        return round($this->size / 1024 / 1024, 2) . ' MB';
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
