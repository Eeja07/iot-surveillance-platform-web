<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraGroup; // Import model Master Group
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CameraGroupController extends Controller
{
    /**
     * Tampilkan halaman manajemen grup
     */
    public function index()
    {
        $user = Auth::user();

        // 1. Ambil semua grup dari tabel master (sinkron dengan data API)
        // Kita gunakan eager loading 'cameras' agar performa lebih baik
        $groups = CameraGroup::where('user_id', $user->id)
            ->with(['cameras'])
            ->get();

        // 2. Ambil kamera yang belum masuk grup (group_id is null)
        $ungroupedCameras = $user->cameras()
            ->whereNull('group_id')
            ->get();

        // 3. Kelompokkan kamera berdasarkan group_id untuk mempermudah akses di view
        $groupedCameras = $user->cameras()
            ->whereNotNull('group_id')
            ->get()
            ->groupBy('group_id');

        // Note: Kita tidak lagi membutuhkan session 'empty_groups'
        // karena grup kosong sekarang tersimpan permanen di database.

        return view('CameraGroups', compact(
            'groups',
            'ungroupedCameras',
            'groupedCameras'
        ));
    }

    /**
     * Buat grup baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'group_name' => 'required|string|max:255',
            'camera_ids' => 'nullable|array',
            'camera_ids.*' => 'exists:cameras,id',
        ]);

        $user = Auth::user();

        // Cek duplikasi nama grup di tabel master
        $exists = CameraGroup::where('user_id', $user->id)
            ->where('name', $request->group_name)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Grup dengan nama tersebut sudah ada!');
        }

        return DB::transaction(function () use ($request, $user) {
            // Simpan Grup ke Tabel Master (Logic sinkron dengan API)
            $group = CameraGroup::create([
                'user_id' => $user->id,
                'name' => $request->group_name
            ]);

            // Jika ada kamera yang dipilih saat pembuatan grup
            if ($request->has('camera_ids') && !empty($request->camera_ids)) {
                $user->cameras()
                    ->whereIn('id', $request->camera_ids)
                    ->update(['group_id' => $group->id]);

                return back()->with('success', "Grup \"{$group->name}\" berhasil dibuat dengan kamera.");
            }

            return back()->with('success', "Grup kosong \"{$group->name}\" berhasil dibuat dan tersimpan di database.");
        });
    }

    /**
     * Update nama grup
     */
    public function update(Request $request, $id) // Sekarang menggunakan ID grup, bukan nama lama
    {
        $request->validate([
            'new_group_name' => 'required|string|max:255',
        ]);

        $group = CameraGroup::where('user_id', Auth::id())->findOrFail($id);

        // Cek duplikasi nama (kecuali untuk grup ini sendiri)
        $exists = CameraGroup::where('user_id', Auth::id())
            ->where('name', $request->new_group_name)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Nama grup "' . $request->new_group_name . '" sudah digunakan!');
        }

        $group->update(['name' => $request->new_group_name]);

        return back()->with('success', 'Nama grup berhasil diubah.');
    }

    /**
     * Assign kamera ke grup
     */
    public function assignCamera(Request $request)
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'group_id' => 'required|exists:camera_groups,id', // Menggunakan ID grup master
        ]);

        $user = Auth::user();
        $camera = $user->cameras()->findOrFail($request->camera_id);
        $group = CameraGroup::where('user_id', $user->id)->findOrFail($request->group_id);

        $camera->update(['group_id' => $group->id]);

        return back()->with('success', 'Kamera "' . $camera->name . '" dimasukkan ke grup "' . $group->name . '"');
    }

    /**
     * Pindahkan kamera dari grup (Set to Ungrouped)
     */
    public function removeCamera(Request $request)
    {
        $request->validate([
            'camera_id' => 'required|exists:cameras,id',
        ]);

        $camera = Auth::user()->cameras()->findOrFail($request->camera_id);
        $camera->update(['group_id' => null]);

        return back()->with('success', 'Kamera "' . $camera->name . '" berhasil dikeluarkan dari grup.');
    }

    /**
     * Hapus grup permanen
     */
    public function destroy($id)
    {
        $group = CameraGroup::where('user_id', Auth::id())->findOrFail($id);

        $name = $group->name;

        // Hapus record grup.
        // Berdasarkan migrasi di Canvas, group_id di tabel cameras akan otomatis menjadi NULL (set null).
        $group->delete();

        return back()->with('success', "Grup \"$name\" berhasil dihapus secara permanen.");
    }
}
