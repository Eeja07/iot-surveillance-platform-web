<?php
namespace App\Http\Controllers\Pages;
use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class UserDashboardController extends Controller
{
    public function updateGroups(Request $request)
    {
        $group = $request->input('group', 'Semua Kamera');
        session(['user_dashboard_camera_group' => $group]);
        return redirect()->back();
    }

    public function index()
    {
        $user = Auth::user();
        $selectedGroup = session('user_dashboard_camera_group', 'Semua Kamera');
        $groups = CameraGroup::where('user_id', $user->id)->pluck('name')->toArray();
        array_unshift($groups, 'Semua Kamera');

        /**
         * SOLUSI JANGKA PANJANG:
         * Tidak perlu eager load image_records sama sekali.
         * latest_image_path dan latest_image_at sudah ada di tabel cameras.
         */
        $cameraQuery = Camera::where('user_id', $user->id)->with(['group', 'latestTelemetry']);

        if ($selectedGroup !== 'Semua Kamera') {
            $cameraQuery->whereHas('group', function($q) use ($selectedGroup) {
                $q->where('name', $selectedGroup);
            });
        }

        $cameras = $cameraQuery->latest()->get();
        $totalCameras = $cameras->count();
        
        $onlineCameras = 0;
        $warningCameras = 0;
        $offlineCameras = 0;

        foreach ($cameras as $camera) {
            $status = $camera->operational_status;
            if ($status === 'ONLINE') {
                $onlineCameras++;
            } elseif ($status === 'WARNING') {
                $warningCameras++;
            } else {
                $offlineCameras++;
            }
        }

        $activeCameras = $onlineCameras;
        $currentGroup = $selectedGroup;

        $cameraIds = $cameras->pluck('id')->toArray();
        $latestDetection = \App\Models\DetectionEvent::where('object_class', 'person')
            ->whereHas('imageRecord', function($q) use ($cameraIds) {
                $q->whereIn('camera_id', $cameraIds);
            })
            ->with('imageRecord.camera')
            ->latest()
            ->first();

        return view('user-dashboard', compact(
            'totalCameras', 'activeCameras',
            'cameras', 'groups', 'currentGroup',
            'onlineCameras', 'warningCameras', 'offlineCameras',
            'latestDetection'
        ));
    }
}
