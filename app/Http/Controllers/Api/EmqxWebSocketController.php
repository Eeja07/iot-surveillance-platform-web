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
                'latest_image_at'   => now(),
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
                    'camera'  => $camera->name,
                    'payload' => $payload
                ]
            );

            $camera->update([
                'last_heartbeat_at' => now()
            ]);
  
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
	];

	$telemetry = collect($allowedFields)
	    ->mapWithKeys(fn ($field) => [
		$field => $payload[$field] ?? null
	    ])
	    ->toArray();

	$telemetry['camera_id'] = $camera->id;

	$telemetry['raw_payload'] = json_encode(
	    $payload,
	    JSON_UNESCAPED_UNICODE
	);

	$telemetryInstance = CameraTelemetry::create($telemetry);
	broadcast(new \App\Events\TelemetryUpdated($camera, $telemetryInstance));
            return response()->json([
                'status' => 'success'
            ]);

        } catch (\Exception $e) {

            Log::error(
                "WS_TELEMETRY_FAILED",
                [
                    'camera' => $camera->name,
                    'error'  => $e->getMessage()
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
}
