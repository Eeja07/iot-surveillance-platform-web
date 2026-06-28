<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * LANGKAH 1: Kirim OTP
     * Endpoint: POST /api/password/email
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Jika email terdaftar, kode OTP telah dikirim.',
            ], 200);
        }

        // Generate 6 digit angka acak
        $otp = (string) random_int(100000, 999999);

        // Simpan OTP ke database
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($otp),
                'created_at' => now()
            ]
        );

        // Kirim email
        try {
            Mail::raw("Kode OTP untuk reset password Anda adalah: {$otp}. Kode ini berlaku selama 60 menit.", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Kode OTP Reset Password - Mivion');
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim email.',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.',
        ], 200);
    }

    /**
     * LANGKAH 2: Verifikasi OTP (Tanpa Reset Password)
     * Endpoint: POST /api/password/verify-otp
     * Digunakan Android untuk validasi sebelum masuk ke screen ganti password.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Cek apakah record ada dan OTP cocok
        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kadaluwarsa.'
            ], 400);
        }

        // Cek kadaluwarsa (misal 60 menit)
        $expires = 60;
        if (now()->parse($record->created_at)->addMinutes($expires)->isPast()) {
            return response()->json(['message' => 'Kode OTP sudah kadaluwarsa.'], 400);
        }

        return response()->json([
            'message' => 'OTP valid. Silakan lanjutkan ke perubahan password.',
        ], 200);
    }

    /**
     * LANGKAH 3: Ganti Password
     * Endpoint: POST /api/password/reset
     */
    public function reset(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'digits:6'], // Diubah dari 'token' menjadi 'otp' sesuai diskusi
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Karena Laravel Password::reset secara internal mencari field bernama 'token',
        // kita memetakan 'otp' dari request ke dalam key 'token' agar bisa diproses.
        $credentials = $request->only('email', 'password', 'password_confirmation');
        $credentials['token'] = $request->otp;

        $status = Password::reset(
            $credentials,
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Hapus semua token akses (Sanctum) agar user harus login ulang
                $user->tokens()->delete();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password berhasil diubah. Silakan login kembali.',
            ], 200);
        }

        return response()->json([
            'message' => 'Gagal meriset password. Pastikan kode OTP benar.',
            'errors' => ['otp' => [__($status)]]
        ], 400);
    }
}
