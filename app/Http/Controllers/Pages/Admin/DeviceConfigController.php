<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraProfile;
use App\Models\ConfigurationHistory;
use App\Services\DeviceConfigurationService;
use Illuminate\Http\Request;

class DeviceConfigController extends Controller
{
    protected $configService;

    public function __construct(DeviceConfigurationService $configService)
    {
        $this->configService = $configService;
    }

    public function index(Request $request)
    {
        $cameras = Camera::with(['latestTelemetry', 'assignedProfile'])->get();
        $profiles = CameraProfile::all();

        // History with filters
        $historyQuery = ConfigurationHistory::with(['camera', 'user'])->orderBy('id', 'desc');

        if ($request->filled('camera_id')) {
            $historyQuery->where('camera_id', $request->camera_id);
        }

        if ($request->filled('status')) {
            $historyQuery->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $historyQuery->whereDate('created_at', $request->date);
        }

        $histories = $historyQuery->paginate(15)->withQueryString();

        return view('content.pages.admin.Device_Config', compact('cameras', 'profiles', 'histories'));
    }

    public function apply(Request $request)
    {
        $request->validate([
            'camera_ids' => 'required|array',
            'camera_ids.*' => 'exists:cameras,id',
            'jpeg_quality' => 'nullable|integer|between:10,63',
            'frame_size' => 'nullable|string|in:QQVGA,QVGA,VGA,SVGA,XGA,SXGA,UXGA',
            'capture_interval_ms' => 'nullable|integer|min:100',
            'telemetry_interval_ms' => 'nullable|integer|min:1000',
            'mqtt_buffer' => 'nullable|integer|min:0',
            'image_enabled' => 'sometimes|boolean',
            'telemetry_enabled' => 'sometimes|boolean',
            'ota_enabled' => 'sometimes|boolean',
        ]);

        $config = [];
        $keys = [
            'jpeg_quality', 'frame_size', 'capture_interval_ms', 
            'telemetry_interval_ms', 'mqtt_buffer'
        ];

        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $config[$key] = $request->input($key);
                if (in_array($key, ['jpeg_quality', 'capture_interval_ms', 'telemetry_interval_ms', 'mqtt_buffer'])) {
                    $config[$key] = (int)$config[$key];
                }
            }
        }

        // Handle booleans which might be absent or false
        if ($request->has('image_enabled_present')) {
            $config['image_enabled'] = $request->boolean('image_enabled');
        }
        if ($request->has('telemetry_enabled_present')) {
            $config['telemetry_enabled'] = $request->boolean('telemetry_enabled');
        }
        if ($request->has('ota_enabled_present')) {
            $config['ota_enabled'] = $request->boolean('ota_enabled');
        }

        try {
            $userId = auth()->id();
            foreach ($request->camera_ids as $cameraId) {
                $camera = Camera::findOrFail($cameraId);
                $this->configService->applyConfig($camera, $config, $userId);
            }

            return redirect()->back()->with('success', 'Configuration command sent successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send configuration: ' . $e->getMessage());
        }
    }

    public function applyProfile(Request $request)
    {
        $request->validate([
            'camera_ids' => 'required|array',
            'camera_ids.*' => 'exists:cameras,id',
            'profile_id' => 'required|exists:camera_profiles,id',
        ]);

        try {
            $userId = auth()->id();
            foreach ($request->camera_ids as $cameraId) {
                $camera = Camera::findOrFail($cameraId);
                $this->configService->applyProfile($camera, $request->profile_id, $userId);
            }

            return redirect()->back()->with('success', 'Profile configuration command sent successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to apply profile: ' . $e->getMessage());
        }
    }

    public function restart(Request $request)
    {
        $request->validate([
            'camera_ids' => 'required|array',
            'camera_ids.*' => 'exists:cameras,id',
        ]);

        try {
            $userId = auth()->id();
            foreach ($request->camera_ids as $cameraId) {
                $camera = Camera::findOrFail($cameraId);
                $this->configService->restartDevice($camera, $userId);
            }

            return redirect()->back()->with('success', 'Restart command sent successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send restart command: ' . $e->getMessage());
        }
    }

    public function factoryReset(Request $request)
    {
        $request->validate([
            'camera_ids' => 'required|array',
            'camera_ids.*' => 'exists:cameras,id',
        ]);

        try {
            $userId = auth()->id();
            foreach ($request->camera_ids as $cameraId) {
                $camera = Camera::findOrFail($cameraId);
                $this->configService->factoryResetDevice($camera, $userId);
            }

            return redirect()->back()->with('success', 'Factory reset command sent successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send factory reset command: ' . $e->getMessage());
        }
    }

    public function fleetOperation(Request $request)
    {
        $request->validate([
            'operation' => 'required|string|in:apply_profile,restart,factory_reset,retry_failed,retry_pending,cancel_pending,reapply_desired',
            'profile_id' => 'required_if:operation,apply_profile|exists:camera_profiles,id',
            'camera_ids' => 'nullable|array',
            'camera_ids.*' => 'exists:cameras,id',
        ]);

        try {
            $cameraIds = $request->input('camera_ids');
            if (empty($cameraIds)) {
                $cameraIds = Camera::pluck('id')->toArray();
            }

            $userId = auth()->id();
            $operation = $request->operation;

            if (in_array($operation, ['retry_failed', 'retry_pending', 'cancel_pending', 'reapply_desired'])) {
                $this->configService->fleetOperation($cameraIds, $operation, $userId);
            } else {
                foreach ($cameraIds as $cameraId) {
                    $camera = Camera::findOrFail($cameraId);
                    if ($operation === 'apply_profile') {
                        $this->configService->applyProfile($camera, $request->profile_id, $userId);
                    } elseif ($operation === 'restart') {
                        $this->configService->restartDevice($camera, $userId);
                    } elseif ($operation === 'factory_reset') {
                        $this->configService->factoryResetDevice($camera, $userId);
                    }
                }
            }

            return redirect()->back()->with('success', 'Fleet operation executed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Fleet operation failed: ' . $e->getMessage());
        }
    }

    public function rollback(Request $request)
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'history_id' => 'required|exists:configuration_histories,id',
        ]);

        try {
            $camera = Camera::findOrFail($request->camera_id);
            $this->configService->rollback($camera, $request->history_id, auth()->id());

            return redirect()->back()->with('success', 'Rollback initiated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to rollback configuration: ' . $e->getMessage());
        }
    }
}
