<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camera;
use Illuminate\Support\Facades\Log;

class MqttAuthController extends Controller
{
    /**
     * Memvalidasi Login (Username & Password)
     */
    public function auth(Request $request)
    {
        // LOG UNTUK DEBUG: Cek file storage/logs/laravel.log
        Log::info("MQTT_AUTH_REQUEST", [
            'received_username' => $request->username,
            'received_password' => $request->password,
            'all_data' => $request->all()
        ]);

        if (empty($request->username) || empty($request->password)) {
            return response()->json(['result' => 'deny'], 200);
        }

        // Cari kamera dengan username dan password (Plain Text)
        // Trim digunakan untuk mencegah error karena spasi tidak sengaja
        $camera = Camera::where('mqtt_username', trim($request->username))
                        ->where('mqtt_password', trim($request->password))
                        ->first();

        if ($camera) {
            Log::info("MQTT_AUTH_SUCCESS: User " . $request->username);
            return response()->json(['result' => 'allow'], 200);
        }

        Log::warning("MQTT_AUTH_FAILED: Invalid credentials for " . $request->username);
        return response()->json(['result' => 'deny'], 200);
    }

    /**
     * Memvalidasi Hak Akses Topik (ACL)
     */
    public function acl(Request $request)
    {
        Log::info("MQTT_ACL_REQUEST", [
            'username' => $request->username,
            'topic' => $request->topic,
            'action' => $request->action
        ]);

        $camera = Camera::where('mqtt_username', trim($request->username))->first();

        if (!$camera) {
            Log::warning("MQTT_ACL_FAILED: Camera not found for user " . $request->username);
            return response()->json(['result' => 'deny'], 200);
        }

        $deviceId = $camera->device_id;
        $topic = $request->topic;

        // Izinkan jika topik mengandung UUID kamera ini
        if (str_contains($topic, $deviceId)) {
            Log::info("MQTT_ACL_ALLOW: User {$request->username} accessing {$topic}");
            return response()->json(['result' => 'allow'], 200);
        }

        Log::warning("MQTT_ACL_DENY: User {$request->username} tried illegal topic: {$topic}");
        return response()->json(['result' => 'deny'], 200);
    }
}
