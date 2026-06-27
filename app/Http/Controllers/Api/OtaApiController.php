<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\OtaFirmware;
use App\Models\OtaDeployment;
use App\Services\OtaDeploymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OtaApiController extends Controller
{
    protected $otaService;

    public function __construct(OtaDeploymentService $otaService)
    {
        $this->otaService = $otaService;
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
     * GET /api/firmware/latest
     */
    public function latest()
    {
        $latest = OtaFirmware::orderBy('created_at', 'desc')->first();

        if (!$latest) {
            return response()->json(['message' => 'No firmware found'], 404);
        }

        return response()->json([
            'id' => $latest->id,
            'version' => $latest->version,
            'board' => $latest->board,
            'model' => $latest->model,
            'build' => $latest->build,
            'min_version' => $latest->min_version,
            'mandatory' => $latest->mandatory,
            'rollback_allowed' => $latest->rollback_allowed,
            'force' => $latest->force,
            'size' => $latest->size,
            'formatted_size' => $latest->formatted_size,
            'sha256' => $latest->sha256,
            'url' => $latest->url,
            'release_notes' => $latest->release_notes,
            'created_at' => $latest->created_at,
        ]);
    }

    /**
     * GET /api/ota/deployments
     */
    public function deployments(Request $request)
    {
        $user = $request->user();
        $cameraIds = $user->cameras()->pluck('id')->toArray();

        $deployments = OtaDeployment::with(['firmware', 'deploymentCameras' => function($query) use ($cameraIds) {
            $query->whereIn('camera_id', $cameraIds)->with('camera');
        }])
        ->whereHas('deploymentCameras', function($query) use ($cameraIds) {
            $query->whereIn('camera_id', $cameraIds);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        $formatted = collect($deployments->items())->map(function ($deployment) {
            return [
                'id' => $deployment->id,
                'firmware_id' => $deployment->firmware_id,
                'target_version' => $deployment->firmware ? $deployment->firmware->version : 'Unknown',
                'status' => $deployment->status,
                'rollout_percentage' => $deployment->rollout_percentage,
                'notes' => $deployment->notes,
                'started_at' => $deployment->started_at,
                'finished_at' => $deployment->finished_at,
                'scheduled_at' => $deployment->scheduled_at,
                'created_at' => $deployment->created_at,
                'cameras' => $deployment->deploymentCameras->map(function ($dc) {
                    return [
                        'camera_id' => $dc->camera_id,
                        'camera_name' => $dc->camera ? $dc->camera->name : 'Unknown',
                        'old_version' => $dc->old_version,
                        'status' => $dc->status,
                        'progress' => $dc->progress,
                        'message' => $dc->message,
                        'started_at' => $dc->started_at,
                        'finished_at' => $dc->finished_at,
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $formatted,
            'meta' => [
                'current_page' => $deployments->currentPage(),
                'last_page' => $deployments->lastPage(),
                'total' => $deployments->total(),
            ],
        ]);
    }

    /**
     * POST /api/cameras/{camera}/ota
     */
    public function deploy(Request $request, Camera $camera)
    {
        $this->checkOwnership($request, $camera);

        $validator = Validator::make($request->all(), [
            'firmware_id' => 'nullable|exists:ota_firmwares,id',
            'scheduled_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $firmwareId = $request->input('firmware_id');
        if (!$firmwareId) {
            // Find latest matching firmware
            $telemetry = $camera->latestTelemetry;
            $query = OtaFirmware::orderBy('created_at', 'desc');
            if ($telemetry) {
                if ($telemetry->fw_board) {
                    $query->where('board', $telemetry->fw_board);
                }
                if ($telemetry->fw_model) {
                    $query->where('model', $telemetry->fw_model);
                }
            }
            $latestFirmware = $query->first() ?: OtaFirmware::orderBy('created_at', 'desc')->first();
            if (!$latestFirmware) {
                return response()->json([
                    'success' => false,
                    'message' => 'No firmware available for deployment.',
                ], 404);
            }
            $firmwareId = $latestFirmware->id;
        }

        $data = [
            'firmware_id' => $firmwareId,
            'target_type' => 'single',
            'camera_ids' => [$camera->id],
            'rollout_percentage' => 100,
            'scheduled_at' => $request->input('scheduled_at'),
            'notes' => $request->input('notes') ?? 'OTA deploy initiated via Mobile API.',
        ];

        try {
            $deployment = $this->otaService->createDeployment($data, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'OTA deployment initiated successfully.',
                'deployment_id' => $deployment->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
