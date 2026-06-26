<?php

namespace App\Events;

use App\Models\DetectionEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PersonDetected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public DetectionEvent $detectionEvent
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('detections'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'person.detected';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        $imageRecord = $this->detectionEvent->imageRecord;
        $camera = $imageRecord?->camera;

        return [
            'id'          => $this->detectionEvent->id,
            'camera_name' => $camera?->name ?? 'Unknown Camera',
            'confidence'  => number_format($this->detectionEvent->confidence * 100, 2) . '%',
            'image_url'   => $imageRecord ? Storage::disk('s3')->url($imageRecord->path) : '',
            'timestamp'   => $this->detectionEvent->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
