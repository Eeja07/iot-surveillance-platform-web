<?php

namespace App\Jobs;

use App\Models\ImageRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DetectImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [5, 15, 45];

    /**
     * The image record instance.
     *
     * @var \App\Models\ImageRecord
     */
    protected $imageRecord;

    /**
     * Create a new job instance.
     *
     * @param \App\Models\ImageRecord $imageRecord
     */
    public function __construct(ImageRecord $imageRecord)
    {
        $this->imageRecord = $imageRecord;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('DetectImageJob started processing image.', [
            'image_record_id' => $this->imageRecord->id,
            'path'            => $this->imageRecord->path,
        ]);

        $url = config('services.detection_service.url', 'http://detection-service:8000') . '/process-image';

        try {
            $response = Http::timeout(10)->post($url, [
                'image_id'   => $this->imageRecord->id,
                'image_path' => $this->imageRecord->path,
                'camera_id'  => $this->imageRecord->camera_id,
            ]);

            if ($response->failed()) {
                Log::warning('Temporary failure calling detection service.', [
                    'image_record_id' => $this->imageRecord->id,
                    'status' => $response->status(),
                ]);
                throw new \Exception("Detection service call failed with status: " . $response->status());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('HTTP Connection failed calling detection service.', [
                'image_record_id' => $this->imageRecord->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        Log::info('DetectImageJob completed successfully.', [
            'image_record_id' => $this->imageRecord->id,
            'response'        => $response->json(),
        ]);
    }
}
