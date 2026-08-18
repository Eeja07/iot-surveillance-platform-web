<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Services\EmqxService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ManajemenKameraController extends Controller
{
    /**
     * Menampilkan daftar semua kamera.
     */
    public function index()
    {
        $user = Auth::user();
        $query = ($user && $user->hasRole('admin'))
            ? Camera::with(['group', 'user'])
            : $user->cameras()->with(['group', 'user']);

        $cameras = $query->latest()->paginate(10);

        return view('content.pages.admin.Manajemen_Kamera', [
            'view' => 'index',
            'cameras' => $cameras
        ]);
    }

    /**
     * Menampilkan formulir untuk membuat kamera baru.
     */
    public function create()
    {
        return view('content.pages.admin.Manajemen_Kamera', ['view' => 'create']);
    }

    /**
     * Menyimpan kamera baru dan mengotomatisasi setup di EMQX secara total.
     */
    public function store(Request $request, EmqxService $emqx)
    {
        $this->authorize('create', Camera::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group_id' => 'nullable|exists:camera_groups,id',
        ]);

        // 1. Buat objek Camera
        $camera = new Camera();
        $camera->fill($request->only('name', 'description', 'group_id'));
        $camera->user_id = Auth::id();
        $camera->save();

        // 2. [OTOMATISASI] Trigger sinkronisasi total ke EMQX
        try {
            $emqx->syncAll();
            Log::info("EMQX Auto-Sync triggered for new camera: " . $camera->name);
        } catch (\Throwable $e) {
            Log::error("EMQX Auto-Sync Failed: " . $e->getMessage());
        }

        return redirect()->route('admin.cameras.edit', $camera->id)
            ->with('success', 'Kamera berhasil didaftarkan! Konfigurasi EMQX telah diperbarui secara otomatis.')
            ->with('newCamera', $camera);
    }

    /**
     * Menampilkan formulir untuk mengedit kamera.
     */
    public function edit(Camera $camera)
    {
        $this->authorize('update', $camera);

        return view('content.pages.admin.Manajemen_Kamera', [
            'view' => 'edit',
            'camera' => $camera
        ]);
    }

    /**
     * Memperbarui data kamera di database.
     */
    public function update(Request $request, Camera $camera)
    {
        $this->authorize('update', $camera);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'admin_enabled' => 'required|boolean',
        ]);

        $camera->update($request->all());

        return redirect()->route('admin.cameras.index')
            ->with('success', 'Data kamera berhasil diperbarui.');
    }

    /**
     * Menghapus kamera dari database dan membersihkan folder gambar di MinIO / S3 & Local.
     */
    public function destroy(Camera $camera)
    {
        $this->authorize('delete', $camera);

        $deviceId = $camera->device_id;
        $directoriesToDelete = [
            "camera/{$deviceId}",
            "camera_images/{$deviceId}",
        ];

        // 1. Bersihkan file di storage (S3/MinIO dan Public disk)
        foreach ($directoriesToDelete as $dir) {
            try {
                if (Storage::disk('s3')->exists($dir)) {
                    Storage::disk('s3')->deleteDirectory($dir);
                    Log::info("MinIO folder deleted: {$dir}");
                }
            } catch (\Throwable $e) {
                Log::warning("Could not delete S3 directory {$dir}: " . $e->getMessage());
            }

            try {
                if (Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->deleteDirectory($dir);
                    Log::info("Public disk folder deleted: {$dir}");
                }
            } catch (\Throwable $e) {
                Log::warning("Could not delete public directory {$dir}: " . $e->getMessage());
            }
        }

        // 2. Hapus record dari database secara transaksional
        try {
            DB::transaction(function () use ($camera) {
                // Hapus relasi jika ada constraint
                $camera->imageRecords()->delete();
                $camera->configurationHistories()->delete();
                \App\Models\CameraTelemetry::where('camera_id', $camera->id)->delete();
                \App\Models\MotionEvent::where('camera_id', $camera->id)->delete();
                DB::table('deployment_cameras')->where('camera_id', $camera->id)->delete();

                // Hapus data kamera
                $camera->delete();
            });

            Log::info("Camera deleted successfully: ID {$camera->id} (UUID: {$deviceId})");

            return redirect()->route('admin.cameras.index')
                ->with('success', 'Kamera dan seluruh data rekaman terkait berhasil dihapus.');
        } catch (\Throwable $e) {
            Log::error("Failed to delete camera ID {$camera->id}: " . $e->getMessage());

            return redirect()->route('admin.cameras.index')
                ->with('error', 'Gagal menghapus kamera: ' . $e->getMessage());
        }
    }

    /**
     * Menghasilkan dan mengunduh QR Code untuk device_id kamera.
     */
    public function downloadQrCode(Camera $camera)
    {
        $this->authorize('view', $camera);

        // Menghasilkan QR code dalam format SVG
        $svg = QrCode::format('svg')->size(300)->generate($camera->device_id);

        $fileName = 'qrcode-device-' . Str::slug($camera->name) . '.svg';

        return response($svg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
