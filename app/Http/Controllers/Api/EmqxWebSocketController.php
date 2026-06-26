<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraTelemetry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EmqxWebSocketController extends Controller
{
    public function handleImage(Request $request)
    {
        $topic = $request->topic;
        $payload = $request->payload;

        preg_match('/ws\/camera\/(.+)\/image/', $topic, $matches);
        $deviceId = $matches[1] ?? null;

        if (!$deviceId || empty($payload)) {
            return response()->json([
                'message' => 'Data tidak lengkap'
            ], 400);
        }

        $camera = Camera::where('device_id', $deviceId)->first();

        if (!$camera) {
            return response()->json([
                'message' => 'Kamera tidak ditemukan'
            ], 404);
        }

        try {
            $imageData = base64_decode($payload);

            $fileName = microtime(true) . '.jpg';

            $path = "camera/{$deviceId}/" . $fileName;

            Storage::disk('s3')->put($path, $imageData);

            $imageRecord = $camera->imageRecords()->create([
                'path' => $path,
                'captured_at' => now()
            ]);

            $camera->update([
                'last_heartbeat_at' => now(),
                'latest_image_path' => $path,
                'latest_image_at' => now(),
            ]);

            Log::info(
                "WS_IMAGE_UPLOAD_SUCCESS dari {$camera->name}"
            );

            broadcast(new \App\Events\NewImageReceived($camera, $imageRecord));

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {

            Log::error(
                "WS_IMAGE_UPLOAD_FAILED: " .
                $e->getMessage()
            );

            return response()->json([
                'status' => 'error',
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function handleTelemetry(Request $request)
    {
        Log::error('TELEMETRY_HANDLER_HIT', [
            'topic' => $request->topic,
            'payload' => $request->payload,
            'all' => $request->all(),
            'raw' => $request->getContent(),
        ]);

        preg_match(
            '/ws\/camera\/(.+)\/telemetry/',
            $request->topic ?? '',
            $matches
        );

        $deviceId = $matches[1] ?? null;

        if (!$deviceId) {
            return response()->json([
                'status' => 'invalid_topic'
            ], 400);
        }

        $camera = Camera::where(
            'device_id',
            $deviceId
        )->first();

        if (!$camera) {
            return response()->json([
                'status' => 'not_found'
            ], 404);
        }

        try {
            $payload = is_array($request->payload)
                ? $request->payload
                : json_decode($request->payload, true);

            Log::info(
                "WS_TELEMETRY_RECEIVED",
                [
                    'camera' => $camera->name,
                    'payload' => $payload
                ]
            );

            $camera->update([
                'last_heartbeat_at' => now()
            ]);

            if (isset($payload['fw_version'])) {
                $payload['firmware'] = $payload['fw_version'];
            }
            if (isset($payload['channel'])) {
                $payload['wifi_channel'] = $payload['channel'];
            }
            if (isset($payload['bssid'])) {
                $payload['wifi_bssid'] = $payload['bssid'];
            }

            $allowedFields = [
                'uptime_sec',
                'free_heap',
                'min_free_heap',
                'free_psram',
                'largest_free_block',
                'fragmentation_pct',
                'rssi',
                'wifi_status',
                'reset_reason',
                'recovery_reason',
                'disconnect_reason',
                'mqtt_connected',
                'mqtt_state',
                'mqtt_uptime_sec',
                'ws_connected',
                'ws_uptime_sec',
                'ws_open_count',
                'ws_close_count',
                'capture_ok',
                'capture_fail',
                'publish_ok',
                'publish_fail',
                'fb_null',
                'camera_reset',
                'wifi_drop',
                'mqtt_reconnect',
                'capture_ms',
                'max_capture_ms',
                'publish_stall',
                'publish_over_interval',
                'transport_recovery',
                'telemetry_publish_fail',
                'last_image_sec',
                'last_publish_attempt_sec',
                'frame_size',
                'max_frame_size',
                'publish_ms',
                'max_publish_ms',
                'mqtt_buffer',
                'base64_size',
                'max_base64_size',
                'loop_counter',

                // BENAR (sesuai firmware OTA)
                'fw_version',
                'fw_build',
                'fw_board',
                'fw_model',

                'ota_supported',
                'ota_running',
                'free_ota_space',

                'wifi_channel',
                'wifi_bssid',
            ];
            $telemetry = collect($allowedFields)
                ->mapWithKeys(fn($field) => [
                    $field => $payload[$field] ?? null
                ])
                ->toArray();// Backward compatibility dengan firmware saat ini
            $telemetry['wifi_channel'] = $payload['wifi_channel'] ?? $payload['channel'] ?? null;
            $telemetry['wifi_bssid'] = $payload['wifi_bssid'] ?? $payload['bssid'] ?? null;

            $telemetry['camera_id'] = $camera->id;

            $telemetry['raw_payload'] = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            );

            $telemetryInstance = CameraTelemetry::create($telemetry);

            // Delegate to config service for Desired State Management checks (Auto Heal & pending queue checks)
            app(\App\Services\DeviceConfigurationService::class)->handleTelemetryUpdate($camera, $payload);

            broadcast(new \App\Events\TelemetryUpdated($camera, $telemetryInstance));
            return response()->json([
                'status' => 'success'
            ]);

        } catch (\Exception $e) {

            Log::error(
                "WS_TELEMETRY_FAILED",
                [
                    'camera' => $camera->name,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'status' => 'error',
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function handleStatus(Request $request)
    {
        preg_match(
            '/ws\/camera\/(.+)\/status/',
            $request->topic ?? '',
            $matches
        );

        $deviceId = $matches[1] ?? null;

        $camera = Camera::where(
            'device_id',
            $deviceId
        )->first();

        if ($camera) {

            $camera->update([
                'last_heartbeat_at' => now()
            ]);

            return response()->json([
                'status' => 'success'
            ]);
        }

        return response()->json([
            'status' => 'not_found'
        ], 404);
    }

    public function handleOtaStatus(Request $request)
    {
        Log::info('WS_OTA_STATUS_HANDLER_HIT', [
            'topic' => $request->topic,
            'payload' => $request->payload,
        ]);

        preg_match(
            '/ws\/camera\/(.+)\/ota\/status/',
            $request->topic ?? '',
            $matches
        );

        $deviceId = $matches[1] ?? null;

        if (!$deviceId) {
            return response()->json([
                'status' => 'invalid_topic'
            ], 400);
        }

        $camera = Camera::where('device_id', $deviceId)->first();

        if (!$camera) {
            return response()->json([
                'status' => 'not_found'
            ], 404);
        }

        try {
            $payload = is_array($request->payload)
                ? $request->payload
                : json_decode($request->payload, true);

            Log::info("WS_OTA_STATUS_RECEIVED", [
                'camera' => $camera->name,
                'payload' => $payload
            ]);

            // Find the active deployment camera record for this camera
            $camRecord = \App\Models\OtaDeploymentCamera::where('camera_id', $camera->id)
                ->whereIn('status', ['Pending', 'Downloading', 'Verifying', 'Flashing'])
                ->orderBy('created_at', 'desc')
                ->first();

            $cameraStatus = $payload['status'] ?? '';
            $status = 'Pending';
            if ($cameraStatus === 'OTA_START' || $cameraStatus === 'OTA_RUNNING' || $cameraStatus === 'OTA_PROGRESS') {
                $status = 'Downloading';
            } elseif ($cameraStatus === 'OTA_VERIFY') {
                $status = 'Verifying';
            } elseif ($cameraStatus === 'OTA_FLASH') {
                $status = 'Flashing';
            } elseif ($cameraStatus === 'OTA_SUCCESS') {
                $status = 'Success';
            } elseif ($cameraStatus === 'OTA_FAILED') {
                $status = 'Failed';
            } elseif ($cameraStatus === 'OTA_CANCELLED') {
                $status = 'Cancelled';
            }

            $progress = isset($payload['progress']) ? intval($payload['progress']) : ($camRecord ? $camRecord->progress : 0);
            $message = $payload['message'] ?? ($payload['reason'] ?? '');
            $version = $payload['version'] ?? ($camRecord ? $camRecord->target_version : 'Unknown');

            if ($camRecord) {
                $duration = null;
                if (in_array($status, ['Success', 'Failed', 'Cancelled'])) {
                    $finishedAt = now();
                    $duration = $camRecord->started_at ? $camRecord->started_at->diffInMilliseconds($finishedAt) : 0;
                    $camRecord->update([
                        'status' => $status,
                        'progress' => $progress,
                        'message' => $message,
                        'finished_at' => $finishedAt,
                        'duration_ms' => $duration,
                    ]);
                } else {
                    $camRecord->update([
                        'status' => $status,
                        'progress' => $progress,
                        'message' => $message,
                    ]);
                }
            }

            // Update latest telemetry record
            $latestTelemetry = $camera->latestTelemetry;
            $otaRunning = in_array($status, ['Downloading', 'Verifying', 'Flashing']);
            $lastOtaResult = $status . ($message ? ': ' . $message : '');

            if ($latestTelemetry) {
                $updateData = [
                    'ota_running' => $otaRunning,
                    'last_ota_result' => $lastOtaResult,
                ];
                if ($status === 'Success') {
                    $updateData['firmware'] = $version;
                    $updateData['last_ota'] = now();
                    $updateData['current_deployment_id'] = null;
                } elseif (in_array($status, ['Failed', 'Cancelled'])) {
                    $updateData['current_deployment_id'] = null;
                }
                $latestTelemetry->update($updateData);
                broadcast(new \App\Events\TelemetryUpdated($camera, $latestTelemetry));
            }

            // Broadcast OTA progress update to Reverb
            $otaData = [
                'version' => $version,
                'status' => $status,
                'progress' => $progress,
                'message' => $message,
                'deployment_id' => $camRecord ? $camRecord->deployment_id : '',
            ];
            broadcast(new \App\Events\OtaStatusUpdated($camera, $otaData));

            // If the status is terminal, trigger next batch rollout checks in the service!
            if ($camRecord && in_array($status, ['Success', 'Failed', 'Cancelled'])) {
                $otaService = app(\App\Services\OtaDeploymentService::class);
                $otaService->startDeploymentBatch($camRecord->deployment);
            }

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error("WS_OTA_STATUS_FAILED: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => $e->getMessage()
            ], 500);
        }
    }

    public function handleConfigStatus(Request $request)
    {
        Log::info('WS_CONFIG_STATUS_HANDLER_HIT', [
            'topic' => $request->topic,
            'payload' => $request->payload,
        ]);

        preg_match(
            '/ws\/camera\/(.+)\/config\/status/',
            $request->topic ?? '',
            $matches
        );

        $deviceId = $matches[1] ?? null;

        if (!$deviceId) {
            return response()->json([
                'status' => 'invalid_topic'
            ], 400);
        }

        $camera = Camera::where('device_id', $deviceId)->first();

        if (!$camera) {
            return response()->json([
                'status' => 'not_found'
            ], 404);
        }

        try {
            $payload = is_array($request->payload)
                ? $request->payload
                : json_decode($request->payload, true);

            $configService = app(\App\Services\DeviceConfigurationService::class);
            $configService->handleConfigStatus($camera, $payload);

            return response()->json([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error("WS_CONFIG_STATUS_FAILED: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
