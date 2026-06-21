<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
class Camera extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'device_id', 'name', 'description', 'api_key',
        'is_active', 'mqtt_username', 'mqtt_password', 'mqtt_status',
        'websocket_channel_id', 'last_heartbeat_at', 'group_id',
        'latest_image_path', 'latest_image_at',  // kolom baru
    ];
    protected $hidden = ['api_key'];
    protected $casts = [
        'last_heartbeat_at' => 'datetime',
        'latest_image_at'   => 'datetime',
    ];
    protected static function booted()
    {
        static::creating(function ($camera) {
            if (empty($camera->device_id)) $camera->device_id = (string) Str::uuid();
            if (empty($camera->mqtt_username)) $camera->mqtt_username = 'mqtt_' . Str::random(8);
            if (empty($camera->mqtt_password)) $camera->mqtt_password = Str::random(16);
            if (empty($camera->api_key)) $camera->api_key = Str::random(40);
        });
    }
    public function getIsActiveAttribute(): bool
    {
        if (empty($this->last_heartbeat_at)) return false;
        return abs(now()->diffInSeconds($this->last_heartbeat_at)) < 15;
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function group(): BelongsTo { return $this->belongsTo(CameraGroup::class, 'group_id'); }

    /**
     * Relasi umum ke semua image records — tanpa orderBy
     */
    public function imageRecords(): HasMany
    {
        return $this->hasMany(ImageRecord::class);
    }
}
