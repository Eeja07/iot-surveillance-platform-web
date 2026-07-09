<?php

namespace App\Console\Commands;

use App\Events\CameraOffline; // 1. Import event CameraOffline
use App\Models\Camera;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CheckOfflineCameras extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'camera:check-offline';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Check for cameras that have not sent a heartbeat recently and mark them as offline';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $this->info('Checking for offline cameras...');

    // Tentukan batas waktu (misalnya 35 detik yang lalu)
    $threshold = Carbon::now()->subSeconds(35);

    // Cari semua kamera yang admin_enabled = true
    $cameras = Camera::where('admin_enabled', true)->get();

    $offlineCount = 0;

    foreach ($cameras as $camera) {
      $online = (bool)($camera->last_heartbeat_at && $camera->last_heartbeat_at->gt($threshold));

      if ($camera->is_online != $online) {
        $camera->update(['is_online' => $online]);

        if ($online) {
          event(new \App\Events\CameraOnline($camera));
          $this->info("Camera '{$camera->name}' marked as online.");
        } else {
          event(new CameraOffline($camera));
          $this->warn("Camera '{$camera->name}' marked as offline.");
          $offlineCount++;
        }
      }
    }

    $this->info('Finished checking cameras. Found ' . $offlineCount . ' newly offline cameras.');
  }
}
