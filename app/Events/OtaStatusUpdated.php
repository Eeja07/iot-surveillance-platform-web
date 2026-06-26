<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtaStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?Camera $camera,
        public array $otaData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('ota-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ota.status.updated';
    }

    public function broadcastWith(): array
    {
        if (!$this->camera) {
            return $this->otaData;
        }

        return [
            'camera_id' => $this->camera->id,
            'device_id' => $this->camera->device_id,
            'camera_name' => $this->camera->name,
            'version' => $this->otaData['version'] ?? '',
            'status' => $this->otaData['status'] ?? 'Pending',
            'progress' => $this->otaData['progress'] ?? 0,
            'message' => $this->otaData['message'] ?? '',
            'deployment_id' => $this->otaData['deployment_id'] ?? '',
        ];
    }
}
