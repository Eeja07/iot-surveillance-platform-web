<?php

namespace App\Http\Controllers\Pages\Log;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogAktifitasController extends Controller
{
  /**
   * Menampilkan halaman log aktivitas.
   */
  public function index()
  {
    // Ambil data log aktivitas, urutkan dari yang terbaru,
    // dan gunakan eager loading untuk mengambil data user terkait.
    $activityLogs = ActivityLog::with('user')
      ->latest()
      ->paginate(20); // Tampilkan 20 log per halaman

    return view('content.pages.Log.Log_Aktifitas', compact('activityLogs'));
  }
}
