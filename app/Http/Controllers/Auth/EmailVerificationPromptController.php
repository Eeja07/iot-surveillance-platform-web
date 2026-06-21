<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Tampilkan prompt verifikasi email.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        // Diubah: Menggunakan 'dashboard.index' agar konsisten dengan controller lainnya
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('dashboard.index', absolute: false))
                    : view('auth.verify-email');
    }
}
