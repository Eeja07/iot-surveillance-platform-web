<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\CameraGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserCameraGroupApiController extends Controller
{
    /**
     * Get All Groups Data
     */
    public function index()
    {
        $user = auth()->user();

        $groups = CameraGroup::where('user_id', $user->id)
            ->with(['cameras'])
            ->get()
            ->map(function ($group) {
                return [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'camera_count' => $group->cameras->count(),
                    'cameras' => $group->cameras
                ];
            });

        $ungroupedCameras = $user->cameras()
            ->whereNull('group_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups,
                'ungrouped_cameras' => $ungroupedCameras
            ]
        ], 200);
    }

    /**
     * Create New Group
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string|max:255',
            'camera_ids' => 'nullable|array',
            'camera_ids.*' => 'exists:cameras,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        $exists = CameraGroup::where('user_id', $user->id)
            ->where('name', $request->group_name)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Nama grup sudah ada.'], 422);
        }

        return DB::transaction(function () use ($request, $user) {
            $group = CameraGroup::create([
                'user_id' => $user->id,
                'name' => $request->group_name
            ]);

            $assignedCount = 0;
            if ($request->has('camera_ids') && !empty($request->camera_ids)) {
                $assignedCount = $user->cameras()
                    ->whereIn('id', $request->camera_ids)
                    ->update(['group_id' => $group->id]);
            }

            return response()->json([
                'success' => true,
                'message' => "Grup '{$group->name}' berhasil dibuat.",
                'data' => [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'assigned_count' => $assignedCount
                ]
            ], 201);
        });
    }

    /**
     * Update Group Name without ID
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_group_name' => 'required|string',
            'new_group_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $group = CameraGroup::where('user_id', $user->id)
            ->where('name', $request->old_group_name)
            ->first();

        if (!$group) {
            return response()->json(['success' => false, 'message' => "Grup '{$request->old_group_name}' tidak ditemukan."], 404);
        }

        $exists = CameraGroup::where('user_id', $user->id)
            ->where('name', $request->new_group_name)
            ->where('id', '!=', $group->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Nama grup baru sudah digunakan.'], 422);
        }

        $group->update(['name' => $request->new_group_name]);

        return response()->json([
            'success' => true,
            'message' => "Nama grup berhasil diubah menjadi '{$group->name}'.",
        ], 200);
    }

    /**
     * Delete Group by Name
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $group = CameraGroup::where('user_id', $user->id)
            ->where('name', $request->group_name)
            ->first();

        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Grup tidak ditemukan.'], 404);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "Grup '{$request->group_name}' berhasil dihapus."
        ], 200);
    }

    /**
     * Assign Camera by Group Name
     */
    public function assignCamera(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'camera_id' => 'required|exists:cameras,id',
            'group_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $camera = $user->cameras()->find($request->camera_id);
        $group = CameraGroup::where('user_id', $user->id)
            ->where('name', $request->group_name)
            ->first();

        if (!$camera || !$group) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        $camera->update(['group_id' => $group->id]);

        return response()->json([
            'success' => true,
            'message' => "Kamera berhasil dimasukkan ke grup '{$group->name}'.",
        ], 200);
    }

    /**
     * Remove Camera from Group
     * Fungsi ini sekarang sudah ditambahkan.
     */
    public function removeCamera(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'camera_id' => 'required|exists:cameras,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $camera = $user->cameras()->find($request->camera_id);

        if (!$camera) {
            return response()->json(['success' => false, 'message' => 'Kamera tidak ditemukan.'], 404);
        }

        $camera->update(['group_id' => null]);

        return response()->json([
            'success' => true,
            'message' => "Kamera berhasil dikeluarkan dari grup.",
        ], 200);
    }
}
