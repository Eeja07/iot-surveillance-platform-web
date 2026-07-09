<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\PrivateChannel;
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
        $userId = null;
        if ($this->camera) {
            $userId = $this->camera->user_id;
        } elseif (isset($this->otaData['deployment_id'])) {
            $deployment = \App\Models\OtaDeployment::find($this->otaData['deployment_id']);
            if ($deployment) {
                $userId = $deployment->created_by;
            }
        }

        if (!$userId) {
            $userId = auth()->id();
        }

        return [
            new PrivateChannel('user.' . $userId . '.ota-updates'),
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
