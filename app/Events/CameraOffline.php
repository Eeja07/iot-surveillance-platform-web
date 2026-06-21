<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CameraOffline implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  /**
   * Buat instance event baru.
   */
  public function __construct(
    public Camera $camera
  ) {
    //
  }

  /**
   * Dapatkan channel tempat event ini akan disiarkan.
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
   * Nama event yang akan didengarkan oleh frontend.
   */
  public function broadcastAs(): string
  {
    return 'camera.offline';
  }

  /**
   * Dapatkan data yang akan di-broadcast.
   *
   * @return array
   */
  public function broadcastWith(): array
  {
    // Kita hanya perlu mengirim ID, karena frontend hanya perlu tahu kamera mana yang offline.
    return ['id' => $this->camera->id];
  }
}
