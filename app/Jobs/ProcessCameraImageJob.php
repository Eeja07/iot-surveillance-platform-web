<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Events\NewImageReceived;
use App\Jobs\DetectImageJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessCameraImageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $deviceId,
        protected string $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $imageData = base64_decode($this->payload);
            if (!$imageData) {
                Log::warning("ASYNC_IMAGE_DECODE_FAILED: Could not decode base64 payload", ['device_id' => $this->deviceId]);
                return;
            }

            $fileName = microtime(true) . '.jpg';
            $path = "camera/{$this->deviceId}/" . $fileName;

            Log::info("ASYNC_MINIO_UPLOADING", ['path' => $path]);

            // Save to MinIO (S3 Disk)
            $disk = Storage::disk('s3');
            $disk->put($path, $imageData, 'public');

            Log::info("ASYNC_MINIO_UPLOAD_SUCCESS", ['path' => $path]);

            // Update database record
            $camera = Camera::where('device_id', $this->deviceId)->first();
            if ($camera) {
                // Administrative Override Check: Reject connection if camera is disabled
                if (!$camera->admin_enabled) {
                    Log::warning("ASYNC_IMAGE_IGNORED: Camera is administratively disabled", ['device_id' => $this->deviceId]);
                    return;
                }

                $wasOffline = !$camera->is_online;

                $camera->update([
                    'last_heartbeat_at' => now(),
                    'is_online'         => true,
                    'latest_image_path' => $path,
                    'latest_image_at'   => now(),
                ]);

                if ($wasOffline) {
                    event(new \App\Events\CameraOnline($camera));
                }

                if (method_exists($camera, 'imageRecords')) {
                    $imageRecord = $camera->imageRecords()->create([
                        'path' => $path,
                        'captured_at' => now()
                    ]);

                    broadcast(new NewImageReceived($camera, $imageRecord));

                    DetectImageJob::dispatch($imageRecord);
                }
            } else {
                Log::warning("ASYNC_IMAGE_CAMERA_NOT_FOUND", ['device_id' => $this->deviceId]);
            }

        } catch (\Exception $e) {
            Log::error("ASYNC_IMAGE_PROCESSING_EXCEPTION: " . $e->getMessage(), [
                'device_id' => $this->deviceId
            ]);
        }
    }
}
