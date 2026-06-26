<?php

namespace App\Events;

use App\Models\Camera;
use App\Models\CameraTelemetry;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Camera $camera,
        public CameraTelemetry $telemetry
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel($this->camera->websocket_channel_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'telemetry.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'camera_id' => $this->camera->id,
            'rssi' => $this->telemetry->formatted_rssi,
            'free_heap' => $this->telemetry->formatted_heap,
            'publish_ms' => $this->telemetry->formatted_publish,
            'mqtt_status' => $this->telemetry->mqtt_status_text,
            'ws_status' => $this->telemetry->ws_status_text,
            'uptime' => $this->telemetry->formatted_uptime,
            'health_status' => $this->telemetry->health_status,
            'firmware' => $this->telemetry->firmware ?: 'N/A',
            'build' => $this->telemetry->build ?: 'N/A',
            'board' => $this->telemetry->board ?: 'N/A',
            'model' => $this->telemetry->model ?: 'N/A',
            'ota_supported' => $this->telemetry->ota_supported ? 'Yes' : 'No',
            'ota_running' => $this->telemetry->ota_running ? 'Yes' : 'No',
            'free_ota_space' => $this->telemetry->free_ota_space ? round($this->telemetry->free_ota_space / 1024 / 1024, 2) . ' MB' : 'N/A',
            'wifi_channel' => $this->telemetry->wifi_channel ?: 'N/A',
            'wifi_bssid' => $this->telemetry->wifi_bssid ?: 'N/A',
            'jpeg_quality' => $this->telemetry->jpeg_quality ?: 'N/A',
            'frame_size' => $this->telemetry->frame_size ?: 'N/A',
            'capture_interval_ms' => $this->telemetry->capture_interval_ms ?: 'N/A',
            'telemetry_interval_ms' => $this->telemetry->telemetry_interval_ms ?: 'N/A',
            'mqtt_buffer' => $this->telemetry->mqtt_buffer ?: 'N/A',
            'image_enabled' => $this->telemetry->image_enabled !== null ? ($this->telemetry->image_enabled ? 'Enabled' : 'Disabled') : 'N/A',
            'telemetry_enabled' => $this->telemetry->telemetry_enabled !== null ? ($this->telemetry->telemetry_enabled ? 'Enabled' : 'Disabled') : 'N/A',
            'ota_enabled' => $this->telemetry->ota_enabled !== null ? ($this->telemetry->ota_enabled ? 'Enabled' : 'Disabled') : 'N/A',
            'assigned_profile' => $this->camera->assignedProfile ? $this->camera->assignedProfile->toArray() : null,
            'last_config_time' => $this->camera->last_config_time ? $this->camera->last_config_time->toDateTimeString() : 'Never',
            'last_sync' => $this->camera->last_sync ? $this->camera->last_sync->toDateTimeString() : 'Never',
        ];
    }
}
