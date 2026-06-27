<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CameraTelemetry extends Model
{
    protected $table = 'camera_telemetry';

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_ota' => 'datetime',
        'mqtt_connected' => 'boolean',
        'ws_connected' => 'boolean',
    ];

    public function getHealthStatusAttribute(): string
    {
        $mqtt = $this->mqtt_connected;
        $ws = $this->ws_connected;
        $publish = $this->publish_ms;
        $rssi = $this->rssi;

        if ($mqtt == 0 || $ws == 0 || $publish > 10000) {
            return 'CRITICAL';
        }
        if ($publish >= 3000 || $rssi < -75) {
            return 'WARNING';
        }
        return 'HEALTHY';
    }

    public function getFormattedRssiAttribute(): string
    {
        return $this->rssi !== null ? $this->rssi . ' dBm' : 'N/A';
    }

    public function getFormattedHeapAttribute(): string
    {
        return $this->free_heap !== null ? round($this->free_heap / 1024, 0) . ' KB' : 'N/A';
    }

    public function getFormattedPublishAttribute(): string
    {
        return $this->publish_ms !== null ? $this->publish_ms . ' ms' : 'N/A';
    }

    public function getMqttStatusTextAttribute(): string
    {
        return $this->mqtt_connected ? 'Online' : 'Offline';
    }

    public function getWsStatusTextAttribute(): string
    {
        return $this->ws_connected ? 'Online' : 'Offline';
    }

    public function getFormattedUptimeAttribute(): string
    {
        if ($this->uptime_sec === null) {
            return 'N/A';
        }
        $hours = floor($this->uptime_sec / 3600);
        $minutes = floor(($this->uptime_sec % 3600) / 60);
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    public function getReconnectDeltaAttribute(): int
    {
        $previous = self::where('camera_id', $this->camera_id)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();
        if (!$previous) {
            return 0;
        }
        $diff = $this->mqtt_reconnect - $previous->mqtt_reconnect;
        return $diff > 0 ? $diff : 0;
    }

    public function getWsCloseDeltaAttribute(): int
    {
        $previous = self::where('camera_id', $this->camera_id)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();
        if (!$previous) {
            return 0;
        }
        $diff = $this->ws_close_count - $previous->ws_close_count;
        return $diff > 0 ? $diff : 0;
    }

    public function getPublishFailDeltaAttribute(): int
    {
        $previous = self::where('camera_id', $this->camera_id)
            ->where('id', '<', $this->id)
            ->orderBy('id', 'desc')
            ->first();
        if (!$previous) {
            return 0;
        }
        $diff = $this->publish_fail - $previous->publish_fail;
        return $diff > 0 ? $diff : 0;
    }

    public function getReconnectDeltaTextAttribute(): string
    {
        return '+' . $this->reconnect_delta;
    }

    public function getWsCloseDeltaTextAttribute(): string
    {
        return '+' . $this->ws_close_delta;
    }

    public function getPublishFailDeltaTextAttribute(): string
    {
        return '+' . $this->publish_fail_delta;
    }
}
