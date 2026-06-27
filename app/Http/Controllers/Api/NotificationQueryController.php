<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetectionEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class NotificationQueryController extends Controller
{
    /**
     * Get paginated notifications (detection events mapped to notification format) for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $cameraIds = $user->cameras()->pluck('id');

        $events = DetectionEvent::whereHas('imageRecord', function ($query) use ($cameraIds) {
            $query->whereIn('camera_id', $cameraIds);
        })
        ->with(['imageRecord.camera'])
        ->latest()
        ->paginate($request->query('per_page', 15));

        $formattedData = collect($events->items())->map(function ($event) use ($user) {
            $imageRecord = $event->imageRecord;
            $camera = $imageRecord?->camera;
            $isRead = Cache::has("user_notification_read_" . $user->id . "_" . $event->id);

            return [
                'id' => $event->id,
                'title' => 'Human Detected',
                'message' => 'Person detected on ' . ($camera?->name ?? 'Unknown Camera'),
                'camera_id' => $camera?->id,
                'camera_name' => $camera?->name ?? 'Unknown Camera',
                'image_url' => $imageRecord ? Storage::disk('s3')->url($imageRecord->path) : null,
                'is_read' => $isRead,
                'created_at' => $event->created_at ? $event->created_at->format('Y-m-d\TH:i:s\Z') : null,
            ];
        });

        return response()->json([
            'data' => $formattedData->toArray(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ]
        ]);
    }

    /**
     * Mark a notification as read.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $cameraIds = $user->cameras()->pluck('id');

        // Check if the event belongs to one of the user's cameras
        $eventExists = DetectionEvent::where('id', $id)
            ->whereHas('imageRecord', function ($query) use ($cameraIds) {
                $query->whereIn('camera_id', $cameraIds);
            })
            ->exists();

        if (!$eventExists) {
            return response()->json(['message' => 'Notification not found or unauthorized.'], 404);
        }

        // Store read status in Cache forever
        Cache::forever("user_notification_read_" . $user->id . "_" . $id, true);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.'
        ]);
    }
}
