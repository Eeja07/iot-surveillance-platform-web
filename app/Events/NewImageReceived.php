<?php

namespace App\Events;

use App\Models\Camera;
use App\Models\ImageRecord;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NewImageReceived implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  /**
   * Create a new event instance.
   */
  public function __construct(
    public Camera $camera,
    public ImageRecord $imageRecord
  ) {}

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array
  {
    return [
      new Channel($this->camera->websocket_channel_id),
    ];
  }

  /**
   * The event's broadcast name.
   */
  public function broadcastAs(): string
  {
    return 'image.received';
  }

  /**
   * Get the data to broadcast.
   *
   * @return array
   */
  public function broadcastWith(): array
  {
    $telemetry = $this->camera->latestTelemetry;
    return [
      'camera_id' => $this->camera->id,
      'image_url' => Storage::disk('s3')->url($this->imageRecord->path),
      'captured_at' => $this->imageRecord->captured_at 
        ? $this->imageRecord->captured_at->format('H:i:s') . ' WIB' 
        : now()->format('H:i:s') . ' WIB',
      'latest_image_timestamp' => $this->camera->latest_image_at 
        ? $this->camera->latest_image_at->timestamp * 1000 
        : now()->timestamp * 1000,
      'is_active' => $this->camera->is_active,
      'mqtt_status' => $this->camera->mqtt_status ?? 'offline',
      'health_status' => $this->camera->operational_status,
      'rssi' => $telemetry ? $telemetry->formatted_rssi : 'N/A',
      'heap' => $telemetry ? $telemetry->formatted_heap : 'N/A',
      'publish_ms' => $telemetry ? $telemetry->formatted_publish : 'N/A',
      'mqtt_connected' => $telemetry ? $telemetry->mqtt_status_text : 'N/A',
      'ws_connected' => $telemetry ? $telemetry->ws_status_text : 'N/A',
      'mqtt_reconnect' => $telemetry ? $telemetry->reconnect_delta_text : '+0',
      'ws_close_count' => $telemetry ? $telemetry->ws_close_delta_text : '+0',
      'publish_fail' => $telemetry ? $telemetry->publish_fail_delta_text : '+0',
      'uptime' => $telemetry ? $telemetry->formatted_uptime : 'N/A',
    ];
  }
}
