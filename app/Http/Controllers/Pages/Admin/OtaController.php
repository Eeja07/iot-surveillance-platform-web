<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\OtaFirmware;
use App\Models\OtaDeployment;
use App\Models\OtaDeploymentCamera;
use App\Services\OtaDeploymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class OtaController extends Controller
{
    protected $otaService;

    public function __construct(OtaDeploymentService $otaService)
    {
        $this->otaService = $otaService;
    }

    public function index(Request $request)
    {
        $firmwares = OtaFirmware::with('uploader')->orderBy('created_at', 'desc')->get();
        $cameras = Camera::with(['latestTelemetry'])->get();
        
        $liveDeployments = OtaDeployment::with(['firmware', 'deploymentCameras.camera'])
            ->whereIn('status', ['Pending', 'Running', 'Scheduled'])
            ->orWhere('started_at', '>=', now()->subHours(12))
            ->orderBy('created_at', 'desc')
            ->get();

        $historyQuery = OtaDeploymentCamera::with(['deployment.firmware', 'camera']);

        if ($request->filled('camera_id')) {
            $historyQuery->where('camera_id', $request->camera_id);
        }
        if ($request->filled('version')) {
            $historyQuery->where('target_version', $request->version);
        }
        if ($request->filled('status')) {
            $historyQuery->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $historyQuery->whereDate('created_at', $request->date);
        }

        $history = $historyQuery->orderBy('created_at', 'desc')->paginate(15);

        return view('content.pages.admin.Ota_Firmware', compact('firmwares', 'cameras', 'liveDeployments', 'history'));
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'version' => 'required|string|regex:/^\d+\.\d+\.\d+$/',
            'firmware_file' => 'required|file|max:2048',
            'board' => 'nullable|string',
            'model' => 'nullable|string',
            'build' => 'nullable|string',
            'min_version' => 'nullable|string',
            'release_notes' => 'nullable|string',
            'mandatory' => 'nullable',
            'rollback_allowed' => 'nullable',
            'force' => 'nullable',
        ]);

        try {
            $this->otaService->uploadFirmware($data, $request->file('firmware_file'), Auth::id());
            return back()->with('success', 'Firmware and manifest uploaded successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to upload firmware: ' . $e->getMessage()]);
        }
    }

    public function download($id)
    {
        $firmware = OtaFirmware::findOrFail($id);
        $firmware->increment('download_count');
        return Storage::disk('s3')->download($firmware->path, "firmware-{$firmware->version}.bin");
    }

    public function destroy($id)
    {
        $firmware = OtaFirmware::findOrFail($id);

        $running = OtaDeployment::where('firmware_id', $firmware->id)
            ->whereIn('status', ['Pending', 'Running', 'Scheduled'])
            ->exists();

        if ($running) {
            return back()->withErrors(['error' => 'Cannot delete firmware while a deployment is running/scheduled.']);
        }

        try {
            Storage::disk('s3')->delete("firmware/{$firmware->version}/firmware.bin");
            Storage::disk('s3')->delete("firmware/{$firmware->version}/manifest.json");
            $firmware->delete();
            return back()->with('success', 'Firmware deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete firmware: ' . $e->getMessage()]);
        }
    }

    public function deploy(Request $request)
    {
        $data = $request->validate([
            'firmware_id' => 'required|exists:ota_firmwares,id',
            'target_type' => 'required|in:single,selected,all',
            'camera_ids' => 'nullable|array',
            'camera_ids.*' => 'exists:cameras,id',
            'rollout_percentage' => 'nullable|in:10,25,50,100',
            'scheduled_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string',
        ]);

        try {
            $deployment = $this->otaService->createDeployment($data, Auth::id());
            return response()->json([
                'success' => true,
                'message' => 'OTA deployment initiated.',
                'deployment_id' => $deployment->id
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'deployment_id' => 'required|exists:ota_deployments,id',
        ]);

        try {
            $this->otaService->cancelDeployment($request->deployment_id);
            return response()->json([
                'success' => true,
                'message' => 'Deployment cancelled.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
