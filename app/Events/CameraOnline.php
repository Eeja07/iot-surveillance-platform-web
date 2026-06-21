<?php

namespace App\Events;

use App\Models\Camera;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
// UBAH BARIS INI
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// DAN UBAH BARIS INI
class CameraOnline implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  /**
   * Create a new event instance.
   *
   * @param \App\Models\Camera $camera
   */
  public function __construct(
    public Camera $camera
  ) {
    //
  }

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
    return 'camera.online';
  }

  /**
   * Get the data to broadcast.
   *
   * @return array
   */
  public function broadcastWith(): array
  {
    return $this->camera->toArray();
  }
}
