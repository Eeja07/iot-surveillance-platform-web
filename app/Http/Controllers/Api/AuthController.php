<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Menangani permintaan registrasi API.
     * Endpoint: POST /api/register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            DB::beginTransaction();

            // 1. Buat User baru
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Tetapkan role 'user'
            $user->assignRole('user');

            // 3. Generate Kode OTP 6 Digit
            $otp = (string) random_int(100000, 999999);

            // 4. Simpan OTP ke tabel password_reset_tokens (Hashed)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'email' => $request->email,
                    'token' => Hash::make($otp),
                    'created_at' => now()
                ]
            );

            // 5. Kirim OTP melalui Email
            Mail::raw("Kode OTP verifikasi pendaftaran Anda adalah: {$otp}. Kode ini berlaku selama 60 menit.", function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Verifikasi Pendaftaran Akun - Mivion');
            });

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email Anda untuk mendapatkan kode OTP verifikasi.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registrasi gagal, silakan coba lagi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint untuk memverifikasi OTP pendaftaran.
     * Endpoint: POST /api/verify-otp
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

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Kode OTP tidak valid atau sudah kadaluwarsa.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Tandai email sebagai terverifikasi
            $user->markEmailAsVerified();

            // Hapus token OTP
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Opsional: Langsung buatkan token login
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Email berhasil diverifikasi.',
                'token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        }

        return response()->json(['message' => 'Pengguna tidak ditemukan.'], 404);
    }

    /**
     * Menangani permintaan login API.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $user = Auth::user();

        // Cek apakah email sudah diverifikasi
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email Anda belum diverifikasi.'], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Menangani permintaan logout API.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout berhasil']);
    }
}
