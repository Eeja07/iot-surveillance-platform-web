<?php

namespace App\Events;

use App\Models\Camera;
use App\Models\ConfigurationHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConfigStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Camera $camera,
        public ConfigurationHistory $history
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('device-configs'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'config.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'camera_id' => $this->camera->id,
            'device_id' => $this->camera->device_id,
            'camera_name' => $this->camera->name,
            'history_id' => $this->history->id,
            'status' => $this->history->status,
            'message' => $this->history->message ?: '',
            'changed_fields' => $this->history->changed_fields,
            'old_config' => $this->history->old_config,
            'new_config' => $this->history->new_config,
            'created_at' => $this->history->created_at ? $this->history->created_at->toDateTimeString() : now()->toDateTimeString(),
            'current_config' => $this->camera->current_config,
            'desired_config' => $this->camera->desired_config,
            'current_config_version' => $this->camera->current_config_version,
            'desired_config_version' => $this->camera->desired_config_version,
            'current_config_hash' => $this->camera->current_config_hash,
            'desired_config_hash' => $this->camera->desired_config_hash,
            'last_applied_at' => $this->camera->last_applied_at ? $this->camera->last_applied_at->format('Y-m-d H:i:s') : 'Never',
            'last_sync' => $this->camera->last_sync ? $this->camera->last_sync->format('Y-m-d H:i:s') : 'Never',
            'last_failure_message' => $this->camera->last_failure_message ?: 'None',
        ];
    }
}
