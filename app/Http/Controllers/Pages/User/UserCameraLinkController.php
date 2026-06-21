<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zxing\QrReader; // Import library QR Code Reader

class UserCameraLinkController extends Controller
{
  public function create()
  {
    return view('content.pages.User.Link-Camera');
  }

  /**
   * Menyimpan (menyinkronkan) kamera ke akun pengguna via Device ID atau unggah gambar QR.
   */
  public function store(Request $request)
  {
    // Aturan validasi: device_id atau qr_image harus ada.
    $request->validate([
      'device_id' => 'required_without:qr_image|nullable|uuid|exists:cameras,device_id',
      // PERUBAHAN: Menghapus aturan 'image' yang terlalu ketat untuk SVG.
      'qr_image' => 'required_without:device_id|nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ], [
      'device_id.exists' => 'Device ID tidak ditemukan atau tidak valid.',
      'qr_image.mimes' => 'Format gambar harus jpeg, png, jpg, gif, atau svg.',
    ]);

    $deviceId = $request->input('device_id');

    // LOGIKA BARU: Jika ada file gambar yang diunggah
    if ($request->hasFile('qr_image')) {
      try {
        $imagePath = $request->file('qr_image')->getPathname();
        $qrcode = new QrReader($imagePath);
        $decodedText = $qrcode->text(); // Ekstrak teks dari QR code

        if (empty($decodedText)) {
          return back()->withErrors(['qr_image' => 'Tidak dapat mendeteksi QR code pada gambar.'])->withInput();
        }
        $deviceId = $decodedText;
      } catch (\Exception $e) {
        // Tangani error jika library gagal membaca gambar
        return back()->withErrors(['qr_image' => 'Gagal memproses gambar QR code. Pastikan gambar jelas.'])->withInput();
      }
    }

    // Cari kamera berdasarkan Device ID yang didapat (baik dari input maupun gambar)
    $camera = Camera::where('device_id', $deviceId)->first();

    // Validasi tambahan setelah mendapat device_id dari gambar
    if (!$camera) {
      return back()->withErrors(['device_id' => 'Device ID dari QR code tidak ditemukan atau tidak valid.'])->withInput();
    }

    // PERBAIKAN: Menggunakan Auth::user() bukan Auth\user()
    $user = Auth::user();

    // Cek kepemilikan sebelumnya
    if ($camera->user_id !== null && !$camera->user->hasRole('admin')) {
      return back()->withErrors(['device_id' => 'Perangkat ini sudah terhubung dengan pengguna lain.'])->withInput();
    }

    // Hubungkan kamera dengan pengguna
    $camera->update([
      'user_id' => $user->id
    ]);

    return redirect()->route('user.my-cameras.index')->with('success', 'Kamera berhasil ditambahkan ke akun Anda!');
  }
}
