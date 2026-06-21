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
    // Kita beri sedikit kelonggaran dari 30 detik
    $threshold = Carbon::now()->subSeconds(35);

    // Cari semua kamera yang aktif tetapi heartbeat terakhirnya sudah lebih dari 35 detik yang lalu
    $offlineCameras = Camera::where('is_active', true)
      ->where('last_heartbeat_at', '<', $threshold)
      ->get();

    if ($offlineCameras->isEmpty()) {
      $this->info('No offline cameras found.');
      return;
    }

    foreach ($offlineCameras as $camera) {
      $camera->is_active = false;
      $camera->save();
      $this->warn("Camera '{$camera->name}' marked as offline.");

      // 2. Panggil event untuk memberitahu frontend
      event(new CameraOffline($camera));
    }

    $this->info('Finished checking cameras. Found ' . $offlineCameras->count() . ' offline cameras.');
  }
}
