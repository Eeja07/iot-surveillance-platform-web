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
      'firmware' => $telemetry ? ($telemetry->firmware ?: 'N/A') : 'N/A',
      'build' => $telemetry ? ($telemetry->build ?: 'N/A') : 'N/A',
      'board' => $telemetry ? ($telemetry->board ?: 'N/A') : 'N/A',
      'model' => $telemetry ? ($telemetry->model ?: 'N/A') : 'N/A',
      'ota_supported' => $telemetry ? ($telemetry->ota_supported ? 'Yes' : 'No') : 'No',
      'ota_running' => $telemetry ? ($telemetry->ota_running ? 'Yes' : 'No') : 'No',
      'free_ota_space' => $telemetry ? ($telemetry->free_ota_space ? round($telemetry->free_ota_space / 1024 / 1024, 2) . ' MB' : 'N/A') : 'N/A',
      'last_ota_result' => $telemetry ? ($telemetry->last_ota_result ?: 'N/A') : 'N/A',
      'last_ota' => $telemetry ? ($telemetry->last_ota ? $telemetry->last_ota->toDateTimeString() : 'N/A') : 'N/A',
      'current_deployment_id' => $telemetry ? ($telemetry->current_deployment_id ?: 'N/A') : 'N/A',
      'wifi_channel' => $telemetry ? ($telemetry->wifi_channel ?: 'N/A') : 'N/A',
      'wifi_bssid' => $telemetry ? ($telemetry->wifi_bssid ?: 'N/A') : 'N/A',
      'jpeg_quality' => $telemetry ? ($telemetry->jpeg_quality ?: 'N/A') : 'N/A',
      'frame_size' => $telemetry ? ($telemetry->frame_size ?: 'N/A') : 'N/A',
      'capture_interval_ms' => $telemetry ? ($telemetry->capture_interval_ms ?: 'N/A') : 'N/A',
      'telemetry_interval_ms' => $telemetry ? ($telemetry->telemetry_interval_ms ?: 'N/A') : 'N/A',
      'mqtt_buffer' => $telemetry ? ($telemetry->mqtt_buffer ?: 'N/A') : 'N/A',
      'image_enabled' => $telemetry ? ($telemetry->image_enabled !== null ? ($telemetry->image_enabled ? 'Enabled' : 'Disabled') : 'N/A') : 'N/A',
      'telemetry_enabled' => $telemetry ? ($telemetry->telemetry_enabled !== null ? ($telemetry->telemetry_enabled ? 'Enabled' : 'Disabled') : 'N/A') : 'N/A',
      'ota_enabled' => $telemetry ? ($telemetry->ota_enabled !== null ? ($telemetry->ota_enabled ? 'Enabled' : 'Disabled') : 'N/A') : 'N/A',
      'assigned_profile' => $this->camera->assignedProfile ? $this->camera->assignedProfile->toArray() : null,
      'last_config_time' => $this->camera->last_config_time ? $this->camera->last_config_time->toDateTimeString() : 'Never',
      'last_sync' => $this->camera->last_sync ? $this->camera->last_sync->toDateTimeString() : 'Never',
    ];
  }
}
