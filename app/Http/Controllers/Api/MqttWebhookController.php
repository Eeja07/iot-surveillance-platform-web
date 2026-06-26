<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Camera;
use Illuminate\Support\Facades\Storage;
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
        Camera::where('mqtt_username', $username)->update([
            'mqtt_status' => $status,
            'last_heartbeat_at' => now()
        ]);
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

        try {
            $imageData = base64_decode($request->payload);
            $fileName = microtime(true) . '.jpg';
            $path = "camera/{$deviceId}/" . $fileName;

            Log::info("MQTT_MINIO_UPLOADING", ['path' => $path]);

            try {
                // Mencoba simpan dengan opsi 'public'
                // Menggunakan disk 's3' yang merujuk ke MinIO
                $disk = Storage::disk('s3');

                // PENTING: Gunakan put agar melempar exception jika gagal (karena 'throw' => true di config)
                $disk->put($path, $imageData, 'public');

                Log::info("MQTT_MINIO_UPLOAD_SUCCESS", ['path' => $path]);

            } catch (\Exception $s3Exception) {
                // MENANGKAP ERROR TEKNIS: misal Invalid Hostname, Access Denied, atau Bucket Not Found
                Log::error("MQTT_S3_DRIVER_ERROR: " . $s3Exception->getMessage(), [
                    'endpoint' => config('filesystems.disks.s3.endpoint'),
                    'bucket' => config('filesystems.disks.s3.bucket'),
                    'path_style' => config('filesystems.disks.s3.use_path_style_endpoint')
                ]);
                return response()->json(['status' => 'driver_error', 'msg' => $s3Exception->getMessage()], 500);
            }

            // Update database record
            $camera = Camera::where('device_id', $deviceId)->first();
            if ($camera) {
                $camera->update([
                    'last_heartbeat_at => now(),
                'is_active' => true,
                    'latest_image_path' => $path,
                    'latest_image_at'   => now(),
                ]);
                if (method_exists($camera, 'imageRecords')) {
                    $imageRecord = $camera->imageRecords()->create([
                        'path' => $path,
                        'captured_at' => now()
                    ]);
                    broadcast(new \App\Events\NewImageReceived($camera, $imageRecord));

            \App\Jobs\DetectImageJob::dispatch($imageRecord);
                }
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error("MQTT_WEBHOOK_EXCEPTION: " . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
