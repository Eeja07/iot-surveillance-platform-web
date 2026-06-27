<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\DetectionEvent;
use App\Models\MotionEvent;
use App\Models\ImageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OverviewController extends Controller
{
    /**
     * Get aggregated overview dashboard statistics for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $cameraIds = $user->cameras()->pluck('id');

        // Total Cameras
        $totalCameras = $cameraIds->count();

        // Online Cameras: count of cameras with operational_status = ONLINE
        // Load the cameras with their latest telemetry to calculate status and averages efficiently.
        $cameras = Camera::where('user_id', $user->id)
            ->with(['latestTelemetry'])
            ->get();

        $onlineCameras = 0;
        foreach ($cameras as $camera) {
            if ($camera->operational_status === 'ONLINE') {
                $onlineCameras++;
            }
        }

        // Detections Today
        $detectionsToday = DetectionEvent::whereHas('imageRecord', function ($query) use ($cameraIds) {
            $query->whereIn('camera_id', $cameraIds);
        })
        ->whereDate('created_at', Carbon::today())
        ->count();

        // Motions Today
        $motionsToday = MotionEvent::whereIn('camera_id', $cameraIds)
            ->whereDate('created_at', Carbon::today())
            ->count();

        // Storage Usage Estimation (assume ~50KB per ImageRecord)
        $totalImagesCount = ImageRecord::whereIn('camera_id', $cameraIds)->count();
        $storageUsageGb = (int) round(($totalImagesCount * 50) / (1024 * 1024));

        // Telemetry Averages (from the latest telemetry of each camera)
        $telemetries = $cameras->map(fn($c) => $c->latestTelemetry)->filter();

        $avgRssi = $telemetries->isEmpty() ? 0 : (int) round($telemetries->avg('rssi'));
        $avgHeap = $telemetries->isEmpty() ? 0 : (int) round($telemetries->avg('free_heap'));
        $uptimeAvg = $telemetries->isEmpty() ? 0 : (int) round($telemetries->avg('uptime_sec'));

        return response()->json([
            'online_cameras' => $onlineCameras,
            'total_cameras' => $totalCameras,
            'detections_today' => $detectionsToday,
            'motions_today' => $motionsToday,
            'storage_usage_gb' => $storageUsageGb,
            'avg_rssi' => $avgRssi,
            'avg_heap' => $avgHeap,
            'uptime_avg' => $uptimeAvg,
        ]);
    }
}
