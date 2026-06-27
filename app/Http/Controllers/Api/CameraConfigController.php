<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Services\DeviceConfigurationService;
use App\Services\EmqxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CameraConfigController extends Controller
{
    protected $configService;
    protected $emqxService;

    public function __construct(DeviceConfigurationService $configService, EmqxService $emqxService)
    {
        $this->configService = $configService;
        $this->emqxService = $emqxService;
    }

    /**
     * Helper to verify camera ownership.
     */
    protected function checkOwnership(Request $request, Camera $camera)
    {
        if ($camera->user_id !== $request->user()->id) {
            abort(403, 'Forbidden. You do not own this camera.');
        }
    }

    /**
     * GET /api/cameras/{camera}/config
     */
    public function show(Request $request, Camera $camera)
    {
        $this->checkOwnership($request, $camera);

        return response()->json([
            'camera_id' => $camera->id,
            'camera_name' => $camera->name,
            'desired_config' => $camera->desired_config,
            'current_config' => $camera->current_config,
            'desired_config_version' => $camera->desired_config_version,
            'current_config_version' => $camera->current_config_version,
            'last_config_status' => $camera->last_config_status,
            'last_failure_message' => $camera->last_failure_message,
        ]);
    }

    /**
     * PUT /api/cameras/{camera}/config
     */
    public function update(Request $request, Camera $camera)
    {
        $this->checkOwnership($request, $camera);

        $validator = Validator::make($request->all(), [
            'jpeg_quality' => 'nullable|integer|between:10,63',
            'frame_size' => 'nullable|string|in:QQVGA,QVGA,VGA,SVGA,XGA,SXGA,UXGA',
            'capture_interval_ms' => 'nullable|integer|min:100',
            'telemetry_interval_ms' => 'nullable|integer|min:1000',
            'mqtt_buffer' => 'nullable|integer|min:0',
            'image_enabled' => 'nullable|boolean',
            'telemetry_enabled' => 'nullable|boolean',
            'ota_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $config = [];
        $fields = [
            'jpeg_quality', 'frame_size', 'capture_interval_ms',
            'telemetry_interval_ms', 'mqtt_buffer', 'image_enabled',
            'telemetry_enabled', 'ota_enabled'
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if (in_array($field, ['jpeg_quality', 'capture_interval_ms', 'telemetry_interval_ms', 'mqtt_buffer'])) {
                    $config[$field] = $val !== null ? (int)$val : null;
                } elseif (in_array($field, ['image_enabled', 'telemetry_enabled', 'ota_enabled'])) {
                    $config[$field] = $val !== null ? (bool)$val : null;
                } else {
                    $config[$field] = $val;
                }
            }
        }

        try {
            $this->configService->applyConfig($camera, $config, $request->user()->id);

            // Fetch fresh status
            $camera->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Configuration command queued successfully.',
                'desired_config_version' => $camera->desired_config_version,
                'last_config_status' => $camera->last_config_status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/cameras/{camera}/reboot
     */
    public function reboot(Request $request, Camera $camera)
    {
        $this->checkOwnership($request, $camera);

        try {
            $this->configService->restartDevice($camera, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Reboot command initiated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/cameras/{camera}/capture
     */
    public function capture(Request $request, Camera $camera)
    {
        $this->checkOwnership($request, $camera);

        try {
            // Check safety if camera is currently OTA/restarting
            $telemetry = $camera->latestTelemetry;
            if ($telemetry && $telemetry->ota_running) {
                return response()->json([
                    'success' => false,
                    'message' => "Safety Reject: Camera {$camera->name} is currently running an OTA upgrade.",
                ], 400);
            }

            // Publish instant capture command
            $topic = "ws/camera/{$camera->device_id}/config";
            $payload = [
                'action' => 'capture',
            ];

            $published = $this->emqxService->publish($topic, $payload);

            if ($published) {
                return response()->json([
                    'success' => true,
                    'message' => 'Manual capture command sent successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to publish capture command via MQTT.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
