<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Models\ConfigurationHistory;
use App\Services\EmqxService;
use App\Events\ConfigStatusUpdated;
use App\Enums\ConfigStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishDeviceConfigurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $camera;
    protected $history;

    public function __construct(Camera $camera, ConfigurationHistory $history)
    {
        $this->camera = $camera;
        $this->history = $history;
    }

    public function backoff()
    {
        return [1, 2, 4];
    }

    public function handle(EmqxService $emqxService)
    {
        $this->camera->refresh();
        $this->history->refresh();

        // If the configuration was cancelled, expired or already applied, stop.
        if (in_array($this->camera->last_config_status, [ConfigStatus::Cancelled->value, ConfigStatus::Expired->value, ConfigStatus::Applied->value])) {
            return;
        }

        // Safety reject: camera currently OTA or restarting
        // If the camera is restarting or doing OTA, we fail the job and let it retry or mark as failed
        $telemetry = $this->camera->latestTelemetry;
        $otaRunning = $telemetry ? $telemetry->ota_running : false;
        
        // Check restarting from history or active flag
        $recentRestart = ConfigurationHistory::where('camera_id', $this->camera->id)
            ->where('status', ConfigStatus::Sending->value)
            ->whereJsonContains('new_config', ['action' => 'restart'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($otaRunning || $recentRestart) {
            throw new \Exception("Camera {$this->camera->name} is currently busy (OTA or Restarting). Retrying configuration later.");
        }

        // Transition status to Sending
        $this->camera->update(['last_config_status' => ConfigStatus::Sending->value]);
        $this->history->update([
            'status' => ConfigStatus::Sending->value,
            'message' => 'Publishing configuration to device via MQTT...'
        ]);
        broadcast(new ConfigStatusUpdated($this->camera, $this->history));

        $topic = "ws/camera/{$this->camera->device_id}/config";
        
        $payload = [
            'action' => 'config',
            'config' => $this->history->new_config,
            'config_version' => $this->history->config_version,
            'config_hash' => $this->history->config_hash,
        ];

        // Format according to whether it's restart, factory_reset or config
        if (isset($payload['config']['action'])) {
            $payload['action'] = $payload['config']['action'];
            unset($payload['config']);
        }

        $published = $emqxService->publish($topic, $payload);

        if ($published) {
            $this->history->update([
                'status' => ConfigStatus::Sending->value,
                'message' => 'Configuration command published. Awaiting confirmation from device.'
            ]);
            broadcast(new ConfigStatusUpdated($this->camera, $this->history));
        } else {
            throw new \Exception("MQTT publish failed for camera {$this->camera->name}.");
        }
    }

    public function failed(\Throwable $exception)
    {
        $this->camera->refresh();
        $this->history->refresh();

        if (in_array($this->camera->last_config_status, [ConfigStatus::Cancelled->value, ConfigStatus::Expired->value])) {
            return;
        }

        $this->camera->update([
            'last_config_status' => ConfigStatus::Failed->value,
            'last_failure_message' => $exception->getMessage()
        ]);

        $this->history->update([
            'status' => ConfigStatus::Failed->value,
            'message' => 'Failed to apply configuration. Error: ' . $exception->getMessage()
        ]);

        broadcast(new ConfigStatusUpdated($this->camera, $this->history));
    }
}
