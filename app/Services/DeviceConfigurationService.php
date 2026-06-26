<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\CameraProfile;
use App\Models\ConfigurationHistory;
use App\Models\CameraTelemetry;
use App\Enums\ConfigStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\EmqxService;
use App\Events\ConfigStatusUpdated;

class DeviceConfigurationService
{
    protected $emqxService;

    public function __construct(EmqxService $emqxService)
    {
        $this->emqxService = $emqxService;
    }

    /**
     * Validate config array.
     */
    public function validateConfig(array $config)
    {
        $validator = Validator::make($config, [
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
            throw new \Exception($validator->errors()->first());
        }

        if (empty($config)) {
            throw new \Exception("Configuration payload cannot be empty.");
        }
    }

    /**
     * Generate SHA256 hash of configuration.
     */
    public function calculateHash(array $config): string
    {
        $keys = ['jpeg_quality', 'frame_size', 'capture_interval_ms', 'telemetry_interval_ms', 'mqtt_buffer', 'image_enabled', 'telemetry_enabled', 'ota_enabled'];
        $normalized = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $config)) {
                $val = $config[$key];
                if ($key === 'image_enabled' || $key === 'telemetry_enabled' || $key === 'ota_enabled') {
                    $normalized[$key] = (bool)$val ? 1 : 0;
                } else {
                    $normalized[$key] = $val;
                }
            }
        }
        ksort($normalized);
        return hash('sha256', json_encode($normalized));
    }

    /**
     * Check if camera configuration has drifted.
     */
    public function isDrifted(Camera $camera): bool
    {
        if (!$camera->desired_config_hash) {
            return false;
        }
        return $camera->desired_config_hash !== $camera->current_config_hash;
    }

    /**
     * Safety validation.
     */
    public function validateSafety(Camera $camera, array $config, ?CameraProfile $profile = null)
    {
        $telemetry = $camera->latestTelemetry;

        // 1. Reject if Camera currently in OTA
        if ($telemetry && $telemetry->ota_running) {
            throw new \Exception("Safety Reject: Camera {$camera->name} is currently running an OTA upgrade.");
        }

        // 2. Reject if Camera is restarting
        $recentRestart = ConfigurationHistory::where('camera_id', $camera->id)
            ->where('status', ConfigStatus::Sending->value)
            ->whereJsonContains('new_config', ['action' => 'restart'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();
        if ($recentRestart) {
            throw new \Exception("Safety Reject: Camera {$camera->name} is currently restarting.");
        }

        // 3. Reject if Profile is incompatible
        if ($profile) {
            if ($profile->ota_enabled && $telemetry && !$telemetry->ota_supported) {
                throw new \Exception("Safety Reject: Profile '{$profile->name}' requires OTA, but camera {$camera->name} does not support OTA.");
            }
        }
    }

    /**
     * Apply config directly.
     */
    public function applyConfig(Camera $camera, array $config, $userId = null, ?CameraProfile $profile = null)
    {
        $this->validateConfig($config);
        $this->validateSafety($camera, $config, $profile);

        return DB::transaction(function () use ($camera, $config, $userId, $profile) {
            $latestTelemetry = $camera->latestTelemetry;

            $oldConfig = [
                'jpeg_quality' => $latestTelemetry ? $latestTelemetry->jpeg_quality : null,
                'frame_size' => $latestTelemetry ? $latestTelemetry->frame_size : null,
                'capture_interval_ms' => $latestTelemetry ? $latestTelemetry->capture_interval_ms : null,
                'telemetry_interval_ms' => $latestTelemetry ? $latestTelemetry->telemetry_interval_ms : null,
                'mqtt_buffer' => $latestTelemetry ? $latestTelemetry->mqtt_buffer : null,
                'image_enabled' => $latestTelemetry ? (bool)$latestTelemetry->image_enabled : null,
                'telemetry_enabled' => $latestTelemetry ? (bool)$latestTelemetry->telemetry_enabled : null,
                'ota_enabled' => $latestTelemetry ? (bool)$latestTelemetry->ota_enabled : null,
            ];

            // Determine version & hash
            $newVersion = ($camera->desired_config_version ?: 0) + 1;
            $newHash = $this->calculateHash($config);

            if ($profile) {
                $newVersion = $profile->config_version;
                $newHash = $profile->config_hash;
            }

            // Create history record
            $history = ConfigurationHistory::create([
                'camera_id' => $camera->id,
                'user_id' => $userId,
                'old_config' => $oldConfig,
                'new_config' => $config,
                'changed_fields' => array_keys($config),
                'status' => ConfigStatus::Pending->value,
                'message' => 'Desired configuration state updated.',
                'config_version' => $newVersion,
                'config_hash' => $newHash,
                'created_at' => now(),
            ]);

            $camera->update([
                'desired_config' => $config,
                'desired_config_version' => $newVersion,
                'desired_config_hash' => $newHash,
                'last_config_status' => ConfigStatus::Pending->value,
                'last_config_time' => now()
            ]);

            // Queue and publish automatically if camera is active
            if ($camera->is_active) {
                $camera->update(['last_config_status' => ConfigStatus::Queued->value]);
                $history->update([
                    'status' => ConfigStatus::Queued->value,
                    'message' => 'Camera is online. Job queued for configuration delivery.'
                ]);
                dispatch(new \App\Jobs\PublishDeviceConfigurationJob($camera, $history));
            } else {
                $history->update([
                    'message' => 'Camera is offline. Configuration cached; will apply upon reconnection.'
                ]);
            }

            broadcast(new ConfigStatusUpdated($camera, $history));

            return $history;
        });
    }

    /**
     * Apply profile.
     */
    public function applyProfile(Camera $camera, $profileId, $userId = null)
    {
        $profile = CameraProfile::findOrFail($profileId);

        $config = [
            'jpeg_quality' => (int)$profile->jpeg_quality,
            'frame_size' => (string)$profile->frame_size,
            'capture_interval_ms' => (int)$profile->capture_interval_ms,
            'telemetry_interval_ms' => (int)$profile->telemetry_interval_ms,
            'mqtt_buffer' => (int)$profile->mqtt_buffer,
            'image_enabled' => (bool)$profile->image_enabled,
            'telemetry_enabled' => (bool)$profile->telemetry_enabled,
            'ota_enabled' => (bool)$profile->ota_enabled,
        ];

        return DB::transaction(function () use ($camera, $profile, $config, $userId) {
            $camera->update(['assigned_profile_id' => $profile->id]);
            return $this->applyConfig($camera, $config, $userId, $profile);
        });
    }

    /**
     * Rollback configuration to a previous history state.
     */
    public function rollback(Camera $camera, $historyId, $userId = null)
    {
        return DB::transaction(function () use ($camera, $historyId, $userId) {
            $history = ConfigurationHistory::findOrFail($historyId);
            $targetConfig = $history->new_config;

            if (empty($targetConfig)) {
                throw new \Exception("Cannot rollback to an empty or invalid history record.");
            }

            $currentTelemetry = $camera->latestTelemetry;
            $oldConfig = [
                'jpeg_quality' => $currentTelemetry ? $currentTelemetry->jpeg_quality : null,
                'frame_size' => $currentTelemetry ? $currentTelemetry->frame_size : null,
                'capture_interval_ms' => $currentTelemetry ? $currentTelemetry->capture_interval_ms : null,
                'telemetry_interval_ms' => $currentTelemetry ? $currentTelemetry->telemetry_interval_ms : null,
                'mqtt_buffer' => $currentTelemetry ? $currentTelemetry->mqtt_buffer : null,
                'image_enabled' => $currentTelemetry ? (bool)$currentTelemetry->image_enabled : null,
                'telemetry_enabled' => $currentTelemetry ? (bool)$currentTelemetry->telemetry_enabled : null,
                'ota_enabled' => $currentTelemetry ? (bool)$currentTelemetry->ota_enabled : null,
            ];

            $newVersion = ($camera->desired_config_version ?: 0) + 1;
            $newHash = $this->calculateHash($targetConfig);

            $rollbackHistory = ConfigurationHistory::create([
                'camera_id' => $camera->id,
                'user_id' => $userId,
                'old_config' => $oldConfig,
                'new_config' => $targetConfig,
                'changed_fields' => array_keys($targetConfig),
                'status' => ConfigStatus::Pending->value,
                'message' => "Rollback initiated. Restoring state from History ID {$history->id}",
                'config_version' => $newVersion,
                'config_hash' => $newHash,
                'rollback_from' => $history->id,
                'rollback_to' => $history->id,
                'created_at' => now(),
            ]);

            $camera->update([
                'desired_config' => $targetConfig,
                'desired_config_version' => $newVersion,
                'desired_config_hash' => $newHash,
                'last_config_status' => ConfigStatus::Pending->value,
                'last_config_time' => now()
            ]);

            if ($camera->is_active) {
                $camera->update(['last_config_status' => ConfigStatus::Queued->value]);
                $rollbackHistory->update([
                    'status' => ConfigStatus::Queued->value,
                    'message' => 'Camera online. Rollback job queued.'
                ]);
                dispatch(new \App\Jobs\PublishDeviceConfigurationJob($camera, $rollbackHistory));
            }

            broadcast(new ConfigStatusUpdated($camera, $rollbackHistory));

            return $rollbackHistory;
        });
    }

    /**
     * Auto Heal and Pending config checking on reconnect.
     */
    public function checkAndPublishPendingConfig(Camera $camera)
    {
        if ($camera->is_active && in_array($camera->last_config_status, [ConfigStatus::Pending->value, ConfigStatus::Queued->value])) {
            $history = ConfigurationHistory::where('camera_id', $camera->id)
                ->whereIn('status', [ConfigStatus::Pending->value, ConfigStatus::Queued->value])
                ->orderBy('id', 'desc')
                ->first();

            if ($history) {
                $camera->update(['last_config_status' => ConfigStatus::Queued->value]);
                $history->update([
                    'status' => ConfigStatus::Queued->value,
                    'message' => 'Camera online. Queueing pending configuration.'
                ]);
                broadcast(new ConfigStatusUpdated($camera, $history));
                dispatch(new \App\Jobs\PublishDeviceConfigurationJob($camera, $history));
            }
        }
    }

    /**
     * Process incoming config status payload from camera.
     */
    public function handleConfigStatus(Camera $camera, array $payload)
    {
        return DB::transaction(function () use ($camera, $payload) {
            $status = $payload['status'] ?? 'success';
            $applied = filter_var($payload['applied'] ?? true, FILTER_VALIDATE_BOOLEAN);
            
            // Map status code or message
            $newStatus = ($status === 'success' && $applied) ? ConfigStatus::Applied->value : ConfigStatus::Rejected->value;
            $message = $payload['message'] ?? (($newStatus === ConfigStatus::Applied->value) ? 'Configuration applied successfully by camera.' : 'Camera rejected configuration.');

            $history = ConfigurationHistory::where('camera_id', $camera->id)
                ->whereIn('status', [ConfigStatus::Sending->value, ConfigStatus::Queued->value, ConfigStatus::Pending->value])
                ->orderBy('id', 'desc')
                ->first();

            if ($history) {
                $history->update([
                    'status' => $newStatus,
                    'message' => $message
                ]);
            }

            $camera->update([
                'last_config_status' => $newStatus,
                'last_sync' => now()
            ]);

            if ($newStatus === ConfigStatus::Applied->value) {
                $camera->update([
                    'current_config' => $camera->desired_config,
                    'current_config_version' => $camera->desired_config_version,
                    'current_config_hash' => $camera->desired_config_hash,
                    'last_applied_at' => now(),
                    'last_failure_message' => null
                ]);

                // Update Telemetry model
                $telemetry = $camera->latestTelemetry ?: new CameraTelemetry(['camera_id' => $camera->id]);
                if ($camera->desired_config) {
                    $telemetry->fill($camera->desired_config);
                }
                $telemetry->config_version = $camera->current_config_version;
                $telemetry->config_hash = $camera->current_config_hash;
                $telemetry->save();

                // Check if restart is required
                $profile = $camera->assignedProfile;
                if ($profile && $profile->restart_required) {
                    Log::info("Restart required by profile '{$profile->name}' for camera {$camera->name}. Triggering restart.");
                    $this->restartDevice($camera, null);
                }

                broadcast(new \App\Events\TelemetryUpdated($camera, $telemetry));
            } else {
                $camera->update([
                    'last_failure_message' => $message
                ]);
            }

            if ($history) {
                broadcast(new ConfigStatusUpdated($camera, $history));
            }

            return true;
        });
    }

    /**
     * Restart device.
     */
    public function restartDevice(Camera $camera, $userId = null)
    {
        return DB::transaction(function () use ($camera, $userId) {
            $history = ConfigurationHistory::create([
                'camera_id' => $camera->id,
                'user_id' => $userId,
                'old_config' => null,
                'new_config' => ['action' => 'restart'],
                'changed_fields' => ['action'],
                'status' => ConfigStatus::Pending->value,
                'message' => 'Restart command initiated.',
                'created_at' => now(),
            ]);

            $camera->update([
                'last_config_status' => ConfigStatus::Pending->value,
            ]);

            broadcast(new ConfigStatusUpdated($camera, $history));

            if ($camera->is_active) {
                $camera->update(['last_config_status' => ConfigStatus::Queued->value]);
                $history->update([
                    'status' => ConfigStatus::Queued->value,
                    'message' => 'Restart command queued.'
                ]);
                dispatch(new \App\Jobs\PublishDeviceConfigurationJob($camera, $history));
            }

            return $history;
        });
    }

    /**
     * Factory reset device.
     */
    public function factoryResetDevice(Camera $camera, $userId = null)
    {
        return DB::transaction(function () use ($camera, $userId) {
            $history = ConfigurationHistory::create([
                'camera_id' => $camera->id,
                'user_id' => $userId,
                'old_config' => null,
                'new_config' => ['action' => 'factory_reset'],
                'changed_fields' => ['action'],
                'status' => ConfigStatus::Pending->value,
                'message' => 'Factory reset command initiated.',
                'created_at' => now(),
            ]);

            $camera->update([
                'last_config_status' => ConfigStatus::Pending->value,
            ]);

            broadcast(new ConfigStatusUpdated($camera, $history));

            if ($camera->is_active) {
                $camera->update(['last_config_status' => ConfigStatus::Queued->value]);
                $history->update([
                    'status' => ConfigStatus::Queued->value,
                    'message' => 'Factory reset command queued.'
                ]);
                dispatch(new \App\Jobs\PublishDeviceConfigurationJob($camera, $history));
            }

            return $history;
        });
    }

    /**
     * Fleet configuration/operation actions.
     */
    public function fleetOperation(array $cameraIds, string $action, $userId = null)
    {
        foreach ($cameraIds as $id) {
            $camera = Camera::findOrFail($id);

            DB::transaction(function () use ($camera, $action, $userId) {
                if ($action === 'retry_failed') {
                    if ($camera->last_config_status === ConfigStatus::Failed->value) {
                        $failedHistory = ConfigurationHistory::where('camera_id', $camera->id)
                            ->where('status', ConfigStatus::Failed->value)
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($failedHistory && $failedHistory->new_config) {
                            $camera->update(['last_config_status' => ConfigStatus::Pending->value]);
                            $newHistory = ConfigurationHistory::create([
                                'camera_id' => $camera->id,
                                'user_id' => $userId,
                                'old_config' => $failedHistory->old_config,
                                'new_config' => $failedHistory->new_config,
                                'changed_fields' => $failedHistory->changed_fields,
                                'status' => ConfigStatus::Pending->value,
                                'message' => 'Retrying failed configuration.',
                                'config_version' => $failedHistory->config_version,
                                'config_hash' => $failedHistory->config_hash,
                                'created_at' => now(),
                            ]);

                            if ($camera->is_active) {
                                $camera->update(['last_config_status' => ConfigStatus::Queued->value]);
                                $newHistory->update(['status' => ConfigStatus::Queued->value]);
                                dispatch(new \App\Jobs\PublishDeviceConfigurationJob($camera, $newHistory));
                            }
                            broadcast(new ConfigStatusUpdated($camera, $newHistory));
                        }
                    }
                } elseif ($action === 'retry_pending') {
                    if (in_array($camera->last_config_status, [ConfigStatus::Pending->value, ConfigStatus::Queued->value])) {
                        $this->checkAndPublishPendingConfig($camera);
                    }
                } elseif ($action === 'cancel_pending') {
                    if (in_array($camera->last_config_status, [ConfigStatus::Pending->value, ConfigStatus::Queued->value, ConfigStatus::Sending->value])) {
                        $camera->update(['last_config_status' => ConfigStatus::Cancelled->value]);
                        
                        $pendingHistory = ConfigurationHistory::where('camera_id', $camera->id)
                            ->whereIn('status', [ConfigStatus::Pending->value, ConfigStatus::Queued->value, ConfigStatus::Sending->value])
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($pendingHistory) {
                            $pendingHistory->update([
                                'status' => ConfigStatus::Cancelled->value,
                                'message' => 'Configuration command cancelled.'
                            ]);
                            broadcast(new ConfigStatusUpdated($camera, $pendingHistory));
                        }
                    }
                } elseif ($action === 'reapply_desired') {
                    if ($camera->desired_config) {
                        $this->applyConfig($camera, $camera->desired_config, $userId, $camera->assignedProfile);
                    }
                }
            });
        }
    }
}
