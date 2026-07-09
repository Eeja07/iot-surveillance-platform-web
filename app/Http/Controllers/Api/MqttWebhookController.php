<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camera;
use Illuminate\Support\Facades\Log;

class MqttWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $action = $request->action;

        Log::info("MQTT_WEBHOOK_TRIGGERED", [
            'action' => $action,
            'topic' => $request->topic,
            'username' => $request->username
        ]);

        switch ($action) {
            case 'client_connected':
                return $this->updateStatus($request->username, 'online');
            case 'client_disconnected':
                return $this->updateStatus($request->username, 'offline');
            case 'message_publish':
                return $this->processImage($request);
            default:
                return response()->json(['status' => 'ignored']);
        }
    }

    protected function updateStatus($username, $status)
    {
        $camera = Camera::where('mqtt_username', $username)->first();
        if ($camera) {
            // Administrative Override Check: Reject connection if camera is disabled
            if (!$camera->is_active) {
                return response()->json(['status' => 'camera_disabled'], 403);
            }

            $camera->update([
                'mqtt_status' => $status,
                'last_heartbeat_at' => now()
            ]);
        }
        return response()->json(['status' => 'ok']);
    }

    protected function processImage(Request $request)
    {
        $topic = $request->topic;
        $parts = explode('/', $topic);
        $deviceId = $parts[2] ?? null;

        if (!$deviceId || empty($request->payload)) {
            return response()->json(['status' => 'invalid_data'], 400);
        }

        $camera = Camera::where('device_id', $deviceId)->first();
        if ($camera) {
            // Administrative Override Check: Reject connection if camera is disabled
            if (!$camera->is_active) {
                return response()->json(['status' => 'camera_disabled'], 403);
            }
        }

        // Dispatch asynchronous image upload and processing job
        \App\Jobs\ProcessCameraImageJob::dispatch($deviceId, $request->payload);

        return response()->json(['status' => 'success']);
    }
}
