<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
  /**
   * Tampilkan halaman registrasi.
   */
  public function create(): View
  {
    return view('auth.register');
  }

  /**
   * Tangani permintaan registrasi yang masuk.
   */
  public function store(Request $request): RedirectResponse
  {
    $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
      'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $user = User::create([
      'name' => $request->name,
      'email' => $request->email,
      'password' => Hash::make($request->password),
    ]);

    $user->assignRole('user');

    // Memicu event Registered yang akan mengirimkan email verifikasi
    event(new Registered($user));

    // Login otomatis setelah registrasi
    Auth::login($user);

    // Diubah: Arahkan ke halaman prompt verifikasi email, bukan langsung ke dashboard
    return redirect()->route('verification.notice');
  }
}
