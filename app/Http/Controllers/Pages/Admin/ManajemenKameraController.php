<?php

namespace App\Http\Controllers\Pages\Admin;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Services\EmqxService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ManajemenKameraController extends Controller
{
    /**
     * Menampilkan daftar semua kamera milik user yang sedang login.
     */
    public function index()
    {
        // Tetap menggunakan relasi user untuk keamanan data
        $cameras = Auth::user()->cameras()->latest()->paginate(10);

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
        $camera->websocket_channel_id = 'camera-status-' . Str::random(16);
        $camera->save();

        // 2. [OTOMATISASI] Trigger sinkronisasi total ke EMQX
        try {
            $emqx->syncAll();
            Log::info("EMQX Auto-Sync triggered for new camera: " . $camera->name);
        } catch (\Exception $e) {
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
            'is_active' => 'required|boolean',
        ]);

        $camera->update($request->all());

        return redirect()->route('admin.cameras.index')
            ->with('success', 'Data kamera berhasil diperbarui.');
    }

    /**
     * Menghapus kamera dari database dan membersihkan folder gambar di MinIO.
     */
    public function destroy(Camera $camera)
    {
        $this->authorize('delete', $camera);

        // 1. Ambil path direktori berdasarkan device_id
        $directoryPath = "camera/{$camera->device_id}";

        try {
            // 2. [UPDATE MINIO] Menghapus direktori dari disk S3 (MinIO)
            // Pastikan konfigurasi 's3' di filesystems.php sudah mengarah ke MinIO
            if (Storage::disk('s3')->exists($directoryPath)) {
                Storage::disk('s3')->deleteDirectory($directoryPath);
                Log::info("MinIO folder deleted for camera: {$camera->device_id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete MinIO folder for camera {$camera->device_id}: " . $e->getMessage());
        }

        // 3. Hapus record dari database
        $camera->delete();

        return redirect()->route('admin.cameras.index')
            ->with('success', 'Kamera dan seluruh data gambar di MinIO berhasil dihapus.');
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
