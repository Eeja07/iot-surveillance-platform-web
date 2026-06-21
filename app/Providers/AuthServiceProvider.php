<?php

namespace App\Providers;

use App\Models\Camera;
use App\Policies\CameraPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log; // <-- Tambahkan ini

class AuthServiceProvider extends ServiceProvider
{
  /**
   * The model to policy mappings for the application.
   *
   * @var array<class-string, class-string>
   */
  protected $policies = [
    Camera::class => CameraPolicy::class,
  ];

  /**
   * Register any authentication / authorization services.
   */
  public function boot(): void
  {
    $this->registerPolicies();

    // --- LANGKAH DEBUGGING FINAL ---
    // Listener ini akan berjalan sebelum policy apa pun dieksekusi.
    Gate::before(function ($user, $ability, $arguments) {
      // Cek apakah argumen pertama adalah instance dari Camera
      if (isset($arguments[0]) && $arguments[0] instanceof Camera) {
        $camera = $arguments[0];
        Log::info('[GATE DEBUG] Pengecekan Izin Dimulai.');
        Log::info('[GATE DEBUG] Aksi: ' . $ability);
        Log::info('[GATE DEBUG] ID Pengguna yang Dicek: ' . $user->id);
        Log::info('[GATE DEBUG] ID Pemilik Kamera yang Dicek: ' . $camera->user_id);
      }
    });
    // --- AKHIR LANGKAH DEBUGGING ---
  }
}
