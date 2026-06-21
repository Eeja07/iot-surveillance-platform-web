<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileApiController extends Controller
{
    /**
     * Mengambil detail profil pengguna yang sedang terautentikasi (GET /api/profile).
     */
    public function show(Request $request)
    {
        $user = $request->user()->loadMissing('roles');

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Perbarui informasi profil pengguna (Nama dan Email).
     * Endpoint: PATCH /api/profile
     */
    public function updateProfile(ProfileUpdateRequest $request)
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Perbarui kata sandi pengguna.
     * Endpoint: PUT /api/password
     */
    public function updatePassword(Request $request)
    {
        // PERBAIKAN: Menghapus argumen guard eksplisit yang tidak terdefinisi (misal: 'api')
        // Jika masalah berlanjut, Anda dapat mengganti 'current_password' dengan 'current_password:web'
        // jika 'web' adalah guard default Anda.
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Hapus semua token Sanctum lama
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Password updated successfully. Please log in again.',
        ], 200);
    }

    /**
     * Hapus akun pengguna.
     * Endpoint: DELETE /api/profile
     */
    public function destroy(Request $request)
    {
        // PERBAIKAN: Menghapus argumen guard eksplisit yang tidak terdefinisi
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ], 200);
    }
}
