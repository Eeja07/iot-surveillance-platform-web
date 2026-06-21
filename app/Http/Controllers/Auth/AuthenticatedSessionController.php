<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
  /**
   * Display the login view.
   */
  public function create(): View
  {
    $pageConfigs = ['layout' => 'blank'];
    return view('auth.login', compact('pageConfigs'));
  }

  /**
   * Handle an incoming authentication request.
   */
  public function store(Request $request): RedirectResponse
  {
    $request->validate([
      'email' => 'required|email',
      'password' => 'required',
    ]);

    if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
      $request->session()->regenerate();

      $user = Auth::user();

      // Cek peran dan arahkan ke dashboard yang sesuai
      if ($user->hasRole('admin')) {
        return redirect()->route('dashboard.index');
      } elseif ($user->hasRole('user')) {
        // Arahkan pengguna dengan peran 'user' ke dashboard pengguna
        return redirect()->route('user.dashboard');
      }

      // Fallback default untuk peran lain
      return redirect()->route('dashboard.index');
    }

    return back()->withErrors([
      'email' => 'Login gagal.',
    ]);
  }

  /**
   * Destroy an authenticated session.
   */
  public function destroy(Request $request): RedirectResponse
  {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
  }
}
