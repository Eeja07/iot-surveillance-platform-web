<?php
namespace App\Http\Controllers\Pages;
use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class DashboardController extends Controller
{
    public function updateGroups(Request $request)
    {
        $group = $request->input('group', 'Semua Kamera');
        session(['dashboard_camera_group' => $group]);
        return redirect()->route('dashboard.index');
    }

    public function index()
    {
        $user = Auth::user();
        $selectedGroup = session('dashboard_camera_group', 'Semua Kamera');
        $groups = CameraGroup::where('user_id', $user->id)->pluck('name')->toArray();
        array_unshift($groups, 'Semua Kamera');

        /**
         * SOLUSI JANGKA PANJANG:
         * Tidak perlu eager load image_records sama sekali.
         * latest_image_path dan latest_image_at sudah ada di tabel cameras.
         * Query ringan — hanya join ke tabel group, tidak ke image_records.
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
        $totalUsers = User::count();
        $currentGroup = $selectedGroup;

        return view('dashboard', compact(
            'totalCameras', 'activeCameras', 'totalUsers',
            'cameras', 'groups', 'currentGroup',
            'onlineCameras', 'warningCameras', 'offlineCameras'
        ));
    }
}
