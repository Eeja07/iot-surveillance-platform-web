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
        ];
    }
}
