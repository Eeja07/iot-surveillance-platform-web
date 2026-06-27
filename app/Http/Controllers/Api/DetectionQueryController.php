<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetectionEvent;
use App\Models\MotionEvent;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DetectionQueryController extends Controller
{
    /**
     * Get paginated detection events for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $cameraIds = $request->user()->cameras()->pluck('id');

        $events = DetectionEvent::whereHas('imageRecord', function ($query) use ($cameraIds) {
            $query->whereIn('camera_id', $cameraIds);
        })
        ->with(['imageRecord.camera'])
        ->latest()
        ->paginate($request->query('per_page', 15));

        return response()->json($this->paginatedResponse($events));
    }

    /**
     * Get the latest detection event for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function latest(Request $request)
    {
        $cameraIds = $request->user()->cameras()->pluck('id');

        $latestEvent = DetectionEvent::whereHas('imageRecord', function ($query) use ($cameraIds) {
            $query->whereIn('camera_id', $cameraIds);
        })
        ->with(['imageRecord.camera'])
        ->latest()
        ->first();

        if (!$latestEvent) {
            return response()->json(['message' => 'No detection events found.'], 404);
        }

        return response()->json($this->formatDetectionEvent($latestEvent));
    }

    /**
     * Get paginated detection events for a specific camera.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Camera  $camera
     * @return \Illuminate\Http\JsonResponse
     */
    public function camera(Request $request, Camera $camera)
    {
        if ($camera->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden: You do not own this camera.'], 403);
        }

        $events = DetectionEvent::whereHas('imageRecord', function ($query) use ($camera) {
            $query->where('camera_id', $camera->id);
        })
        ->with(['imageRecord.camera'])
        ->latest()
        ->paginate($request->query('per_page', 15));

        return response()->json($this->paginatedResponse($events));
    }

    /**
     * Get paginated detection events history with optional filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $cameraIds = $request->user()->cameras()->pluck('id');

        $query = DetectionEvent::whereHas('imageRecord', function ($q) use ($cameraIds) {
            $q->whereIn('camera_id', $cameraIds);
        });

        if ($request->filled('camera_id')) {
            $query->whereHas('imageRecord', function ($q) use ($request) {
                $q->where('camera_id', $request->query('camera_id'));
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->query('date'));
        }

        $events = $query->with(['imageRecord.camera'])
            ->latest()
            ->paginate($request->query('per_page', 15));

        return response()->json($this->paginatedResponse($events));
    }

    /**
     * Get paginated motion events for the authenticated user with optional filters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function motion(Request $request)
    {
        $cameraIds = $request->user()->cameras()->pluck('id');

        $query = MotionEvent::whereIn('camera_id', $cameraIds);

        if ($request->filled('camera_id')) {
            $query->where('camera_id', $request->query('camera_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->query('date'));
        }

        $events = $query->with(['camera', 'imageRecord'])
            ->latest()
            ->paginate($request->query('per_page', 15));

        $formattedData = collect($events->items())->map(function ($event) {
            return $this->formatMotionEvent($event);
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
     * Format a single DetectionEvent object.
     *
     * @param  \App\Models\DetectionEvent  $event
     * @return array
     */
    private function formatDetectionEvent(DetectionEvent $event): array
    {
        $imageRecord = $event->imageRecord;
        $camera = $imageRecord?->camera;

        return [
            'id' => $event->id,
            'camera_id' => $camera?->id,
            'camera_name' => $camera?->name ?? 'Unknown Camera',
            'image_record_id' => $imageRecord?->id,
            'image_url' => $imageRecord ? Storage::disk('s3')->url($imageRecord->path) : '',
            'confidence' => (float) $event->confidence,
            'detected_at' => $event->created_at ? $event->created_at->format('Y-m-d\TH:i:s\Z') : null,
        ];
    }

    /**
     * Format a single MotionEvent object.
     *
     * @param  \App\Models\MotionEvent  $event
     * @return array
     */
    private function formatMotionEvent(MotionEvent $event): array
    {
        $camera = $event->camera;
        $imageRecord = $event->imageRecord;

        return [
            'id' => $event->id,
            'camera_id' => $camera?->id,
            'camera_name' => $camera?->name ?? 'Unknown Camera',
            'image_record_id' => $imageRecord?->id,
            'image_url' => $imageRecord ? Storage::disk('s3')->url($imageRecord->path) : '',
            'motion_score' => (float) $event->motion_score,
            'person_confidence' => $event->person_confidence !== null ? (float) $event->person_confidence : null,
            'detected_at' => $event->created_at ? $event->created_at->format('Y-m-d\TH:i:s\Z') : null,
        ];
    }

    /**
     * Helper to structure a paginated response.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @return array
     */
    private function paginatedResponse($paginator): array
    {
        $formattedData = collect($paginator->items())->map(function ($event) {
            return $this->formatDetectionEvent($event);
        });

        return [
            'data' => $formattedData->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ]
        ];
    }
}
