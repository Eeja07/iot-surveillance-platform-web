<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\OtaFirmware;
use App\Models\OtaDeployment;
use App\Models\OtaDeploymentCamera;
use App\Models\CameraTelemetry;
use App\Services\EmqxService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OtaDeploymentService
{
    protected $emqxService;

    public function __construct(EmqxService $emqxService)
    {
        $this->emqxService = $emqxService;
    }

    /**
     * Upload firmware binary, generate manifest, and save metadata.
     */
    public function uploadFirmware(array $data, $file, $userId = null)
    {
        return DB::transaction(function () use ($data, $file, $userId) {
            $version = $data['version'];
            $board = $data['board'] ?? 'ESP32-CAM';
            $model = $data['model'] ?? 'AI_THINKER';
            $build = $data['build'] ?? now()->format('YmdHis');
            $minVersion = $data['min_version'] ?? null;
            $mandatory = filter_var($data['mandatory'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $rollbackAllowed = filter_var($data['rollback_allowed'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $force = filter_var($data['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $releaseNotes = $data['release_notes'] ?? '';

            // Compute SHA256 of the binary
            $binaryContents = file_get_contents($file->getRealPath());
            $sha256 = hash('sha256', $binaryContents);
            $size = $file->getSize();            if (OtaFirmware::where('sha256', $sha256)->exists()) {
                throw new \Exception("A firmware binary with this SHA256 checksum already exists.");
            }

            // Save firmware binary to MinIO
            $firmwarePath = "firmware/{$version}/firmware_{$build}.bin";
            Storage::disk('s3')->put($firmwarePath, $binaryContents);
            $url = Storage::disk('s3')->url($firmwarePath);

            // Auto-generate manifest.json
            $manifest = [
                'version' => $version,
                'board' => $board,
                'model' => $model,
                'build' => $build,
                'min_version' => $minVersion ?: "",
                'mandatory' => $mandatory,
                'rollback_allowed' => $rollbackAllowed,
                'force' => $force,
                'size' => $size,
                'sha256' => $sha256,
                'url' => $url,
                'release_notes' => $releaseNotes
            ];

            $manifestPath = "firmware/{$version}/manifest_{$build}.json";
            Storage::disk('s3')->put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Create record
            return OtaFirmware::create([
                'version' => $version,
                'board' => $board,
                'model' => $model,
                'build' => $build,
                'min_version' => $minVersion,
                'mandatory' => $mandatory,
                'rollback_allowed' => $rollbackAllowed,
                'force' => $force,
                'size' => $size,
                'sha256' => $sha256,
                'url' => $url,
                'path' => $firmwarePath,
                'release_notes' => $releaseNotes,
                'uploaded_by' => $userId,
                'download_count' => 0,
                'deploy_count' => 0,
            ]);
        });
    }

    /**
     * Create a deployment.
     */
    public function createDeployment(array $data, $userId = null)
    {
        return DB::transaction(function () use ($data, $userId) {
            $firmwareId = $data['firmware_id'];
            $targetType = $data['target_type']; // single, selected, fleet
            $cameraIds = $data['camera_ids'] ?? [];
            $rolloutPercentage = intval($data['rollout_percentage'] ?? 100);
            $scheduledAt = isset($data['scheduled_at']) && !empty($data['scheduled_at']) ? \Carbon\Carbon::parse($data['scheduled_at']) : null;
            $notes = $data['notes'] ?? '';

            $firmware = OtaFirmware::findOrFail($firmwareId);

            // Resolve target cameras
            $query = Camera::where('is_active', true);
            if ($targetType === 'single') {
                $query->where('id', $cameraIds[0] ?? null);
            } elseif ($targetType === 'selected') {
                $query->whereIn('id', $cameraIds);
            }
            $cameras = $query->get();

            if ($cameras->isEmpty()) {
                throw new \Exception("No active target cameras found for deployment.");
            }

            // SAFETY CHECKS:
            foreach ($cameras as $camera) {
                $telemetry = $camera->latestTelemetry;

                // 1. Prevent Deploy while OTA running
                if ($telemetry && $telemetry->ota_running) {
                    throw new \Exception("Camera '{$camera->name}' is already running an active OTA update.");
                }

                // 2. Prevent deploy unsupported board/model
                if ($telemetry) {
                    if ($telemetry->fw_board && strtolower($telemetry->fw_board) !== strtolower($firmware->board)) {
                        throw new \Exception("Board mismatch for '{$camera->name}': camera is {$telemetry->fw_board}, firmware requires {$firmware->board}.");
                    }
                    if ($telemetry->fw_model && strtolower($telemetry->fw_model) !== strtolower($firmware->model)) {
                        throw new \Exception("Model mismatch for '{$camera->name}': camera is {$telemetry->fw_model}, firmware requires {$firmware->model}.");
                    }
                }
            }

            // Create Deployment Record
            $deployment = OtaDeployment::create([
                'firmware_id' => $firmware->id,
                'created_by' => $userId,
                'status' => $scheduledAt && $scheduledAt->isFuture() ? 'Scheduled' : 'Pending',
                'scheduled_at' => $scheduledAt,
                'rollout_percentage' => $rolloutPercentage,
                'notes' => $notes,
            ]);

            // Create Deployment Camera Records
            $isStaged = $rolloutPercentage < 100;
            foreach ($cameras as $index => $camera) {
                $telemetry = $camera->latestTelemetry;
                
                // Determine initial status for rollout stage
                $initialStatus = 'Pending';
                if ($scheduledAt && $scheduledAt->isFuture()) {
                    $initialStatus = 'Staged';
                } elseif ($isStaged) {
                    // For deploy now with rollout: determine if this camera belongs to the first batch
                    $batchSize = max(1, ceil($cameras->count() * $rolloutPercentage / 100));
                    if ($index >= $batchSize) {
                        $initialStatus = 'Staged';
                    }
                }

                OtaDeploymentCamera::create([
                    'deployment_id' => $deployment->id,
                    'camera_id' => $camera->id,
                    'old_version' => $telemetry ? $telemetry->firmware : 'Unknown',
                    'target_version' => $deployment->firmware->version,
                    'status' => $initialStatus,
                    'progress' => 0,
                ]);
            }

            // Increment deploy count on firmware
            $firmware->increment('deploy_count');

            // If not scheduled, trigger start immediately
            if (!$scheduledAt || $scheduledAt->isPast()) {
                $this->startDeploymentBatch($deployment);
            }

            return $deployment;
        });
    }

    /**
     * Start/resume deployment batches for rollout and schedule execution.
     */
    public function startDeploymentBatch(OtaDeployment $deployment)
    {
        DB::transaction(function () use ($deployment) {
            $cameras = $deployment->deploymentCameras;
            
            // Check status of all cameras in deployment
            $allSuccess = true;
            $anyFailed = false;
            $runningCount = 0;

            foreach ($cameras as $cam) {
                if (in_array($cam->status, ['Downloading','Verifying','Flashing'])) {
                    $runningCount++;
                    $allSuccess = false;
                } elseif (in_array($cam->status, ['Failed', 'Cancelled'])) {
                    $anyFailed = true;
                    $allSuccess = false;
                }
            }

            if ($anyFailed) {
                // If any camera in the current active batch failed, fail/cancel the entire deployment
                $deployment->update([
                    'status' => 'Failed',
                    'finished_at' => now(),
                ]);
                // Mark remaining Staged/Pending cameras as Cancelled
                foreach ($cameras as $cam) {
                    if (in_array($cam->status, ['Pending', 'Staged'])) {
                        $cam->update([
                            'status' => 'Cancelled',
                            'finished_at' => now(),
                        ]);
                        $this->clearCameraOtaState($cam->camera);
                    }
                }
                $this->broadcastFleetUpdate($deployment);
                return;
            }

            if ($runningCount > 0) {
                // Currently running, let it run
                if ($deployment->status !== 'Running') {
                    $deployment->update([
                        'status' => 'Running',
                        'started_at' => now(),
                    ]);
                }
                return;
            }

            // All currently processed cameras are Success!
            $pendingOrStaged = $cameras->filter(fn($c) => $c->status === 'Staged' || $c->status === 'Pending');

            if ($pendingOrStaged->isEmpty()) {
                // All done!
                $deployment->update([
                    'status' => 'Success',
                    'finished_at' => now(),
                ]);
                $this->broadcastFleetUpdate($deployment);
                return;
            }

            // Time to start the next batch!
            if ($deployment->status !== 'Running') {
                $deployment->update([
                    'status' => 'Running',
                    'started_at' => now(),
                ]);
            }

            // Calculate batch size
            $totalCount = $cameras->count();
            $percentage = $deployment->rollout_percentage;
            $batchSize = max(1, ceil($totalCount * $percentage / 100));

            // Select next set of Staged/Pending cameras up to Batch Size
            $nextBatch = $pendingOrStaged->take($batchSize);

            foreach ($nextBatch as $cam) {
                $cam->update([
                    'status' => 'Pending',
                    'started_at' => now(),
                ]);

                // Update Camera Telemetry State
                $camera = $cam->camera;
                $telemetry = $camera->latestTelemetry;
                if ($telemetry) {
                    $telemetry->update([
                        'ota_running' => true,
                    ]);
                    broadcast(new \App\Events\TelemetryUpdated($camera, $telemetry));
                }

                // Publish MQTT deployment command
                $manifestUrl = Storage::disk('s3')->url("firmware/{$deployment->firmware->version}/manifest_{$deployment->firmware->build}.json");
                $payload = [
                    'action' => 'ota',
                    'manifest' => $manifestUrl
                ];

                $topic = "ws/camera/{$camera->device_id}/ota";
                Log::info("OTA_PUBLISH_START",["topic"=>$topic,"payload"=>$payload]);
                $ok=$this->emqxService->publish($topic,$payload);
                Log::info("OTA_PUBLISH_RESULT",["success"=>$ok]);
            }

            $this->broadcastFleetUpdate($deployment);
        });
    }

    /**
     * Clear camera OTA state when done.
     */
    protected function clearCameraOtaState(Camera $camera)
    {
        $telemetry = $camera->latestTelemetry;
        if ($telemetry) {
            $telemetry->update([
                'ota_running' => false,
            ]);
            broadcast(new \App\Events\TelemetryUpdated($camera, $telemetry));
        }
    }

    /**
     * Cancel an active deployment.
     */
    public function cancelDeployment($deploymentId)
    {
        return DB::transaction(function () use ($deploymentId) {
            $deployment = OtaDeployment::findOrFail($deploymentId);
            if (!in_array($deployment->status, ['Pending', 'Running', 'Scheduled'])) {
                throw new \Exception("Only active or scheduled deployments can be cancelled.");
            }

            $deployment->update([
                'status' => 'Cancelled',
                'finished_at' => now(),
            ]);

            foreach ($deployment->deploymentCameras as $cam) {
                if (in_array($cam->status, ['Pending', 'Downloading', 'Verifying', 'Flashing', 'Staged'])) {
                    $cam->update([
                        'status' => 'Cancelled',
                        'finished_at' => now(),
                    ]);
                    $this->clearCameraOtaState($cam->camera);
                }
            }

            $this->broadcastFleetUpdate($deployment);
            return $deployment;
        });
    }

    /**
     * Broadcast live fleet update to Echo.
     */
    public function broadcastFleetUpdate(OtaDeployment $deployment)
    {
        $cameras = $deployment->deploymentCameras;
        $total = $cameras->count();
        $success = $cameras->where('status', 'Success')->count();
        $failed = $cameras->where('status', 'Failed')->count();
        $cancelled = $cameras->where('status', 'Cancelled')->count();
        $completed = $success + $failed + $cancelled;
        
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;

        $elapsed = 0;
        if ($deployment->started_at) {
            $end = $deployment->finished_at ?: now();
            $elapsed = $deployment->started_at->diffInSeconds($end);
        }

        $data = [
            'type' => 'fleet_update',
            'deployment_id' => $deployment->id,
            'status' => $deployment->status,
            'progress' => $progress,
            'success_count' => $success,
            'failed_count' => $failed,
            'cancelled_count' => $cancelled,
            'completed_count' => $completed,
            'total_count' => $total,
            'elapsed_seconds' => $elapsed,
        ];

        broadcast(new \App\Events\OtaStatusUpdated(null, $data));
    }
}
