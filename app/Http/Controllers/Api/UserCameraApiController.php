<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Zxing\QrReader; // Pastikan library ini terinstall

class UserCameraApiController extends Controller
{
    /**
     * List Kamera (Pengganti UserManajemenKameraController@getData)
     * Mengembalikan daftar kamera milik user dengan pagination.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Query dasar
        $query = $user->cameras();

        // Fitur Pencarian (Search)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('device_id', 'like', "%{$search}%");
            });
        }

        // Pagination (10 item per halaman)
        // Android bisa mengirim parameter ?page=1, ?page=2, dst.
        $cameras = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kamera berhasil diambil.',
            'data'    => $cameras
        ], 200);
    }

    /**
     * Link Kamera (Pengganti UserCameraLinkController@store)
     * Menerima device_id string ATAU file gambar QR.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'device_id' => 'required_without:qr_image|nullable|string|exists:cameras,device_id',
            'qr_image'  => 'required_without:device_id|nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $deviceId = $request->input('device_id');

        // 2. Logika Scan QR (Jika upload gambar)
        // Catatan: Untuk Android, lebih baik scan QR dilakukan di sisi HP (Client Side)
        // dan hanya mengirim string device_id ke sini agar lebih cepat.
        // Namun fitur ini tetap kita sediakan untuk fallback.
        if ($request->hasFile('qr_image')) {
            try {
                $imagePath = $request->file('qr_image')->getPathname();
                $qrcode = new QrReader($imagePath);
                $decodedText = $qrcode->text();

                if (empty($decodedText)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'QR Code tidak terbaca pada gambar.'
                    ], 400);
                }
                $deviceId = $decodedText;
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal memproses gambar QR Code.'
                ], 500);
            }
        }

        // 3. Cari Kamera
        $camera = Camera::where('device_id', $deviceId)->first();

        if (!$camera) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID tidak ditemukan.'
            ], 404);
        }

        // 4. Cek Kepemilikan
        // Jika sudah ada yang punya DAN pemiliknya bukan admin (user lain), tolak.
        if ($camera->user_id !== null && !$camera->user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Kamera ini sudah terhubung dengan pengguna lain.'
            ], 403);
        }

        // 5. Update Pemilik
        $camera->update([
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kamera berhasil ditambahkan ke akun Anda.',
            'data'    => $camera
        ], 200);
    }

    /**
     * Update Detail Kamera (Edit Nama/Deskripsi)
     */
    public function update(Request $request, $id)
    {
        // Cari kamera milik user yang sedang login
        $camera = auth()->user()->cameras()->find($id);

        if (!$camera) {
            return response()->json([
                'success' => false,
                'message' => 'Kamera tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $camera->update($request->only(['name', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Data kamera berhasil diperbarui.',
            'data'    => $camera
        ], 200);
    }

    /**
     * Unlink/Hapus Kamera dari Akun
     * Mengembalikan kepemilikan ke Admin (seperti logika controller web Anda).
     */
    public function destroy($id)
    {
        $camera = auth()->user()->cameras()->find($id);

        if (!$camera) {
            return response()->json([
                'success' => false,
                'message' => 'Kamera tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        // Cari admin untuk menampung kamera "yatim piatu"
        $admin = User::role('admin')->first();

        $camera->user_id = $admin ? $admin->id : null;
        $camera->save();

        return response()->json([
            'success' => true,
            'message' => 'Kamera berhasil dilepaskan dari akun Anda.'
        ], 200);
    }

    /**
     * Detail Satu Kamera (Opsional, untuk halaman detail di Android)
     */
    public function show($id)
    {
        $camera = auth()->user()->cameras()->find($id);

        if (!$camera) {
            return response()->json([
                'success' => false,
                'message' => 'Kamera tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $camera
        ], 200);
    }
}
