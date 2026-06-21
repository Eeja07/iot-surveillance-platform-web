<?php

namespace App\Http\Controllers\Pages\Log;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\ImageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class RiwayatRekamanController extends Controller
{
  /**
   * Menampilkan halaman awal untuk memilih kamera.
   */
  public function index()
  {
    $cameras = Auth::user()->cameras()->latest()->paginate(10);
    return view('content.pages.Log.Riwayat_Rekaman_index', compact('cameras'));
  }

  /**
   * Fungsi utama untuk menampilkan riwayat dalam mode "explorer".
   *
   * @param \App\Models\Camera $camera
   * @param string|null $date (Format: YYYY-MM-DD)
   * @param string|null $hour (Format: HH)
   * @param string|null $minute (Format: MM)
   * @param string|null $chunk (Halaman/grup gambar)
   * @return \Illuminate\View\View
   */
  public function showExplorer(Request $request, Camera $camera, $date = null, $hour = null, $minute = null, $chunk = null)
  {
    $this->authorize('update', $camera);

    // BARU: Ambil data jam & menit yang tersedia untuk filter
    $availableTimes = [];
    if ($date) {
      $timeSlots = ImageRecord::where('camera_id', $camera->id)
        ->whereDate('captured_at', $date)
        ->select(DB::raw('DISTINCT HOUR(captured_at) as hour, MINUTE(captured_at) as minute'))
        ->orderBy('hour')->orderBy('minute')
        ->get();

      foreach ($timeSlots as $slot) {
        $hourKey = str_pad($slot->hour, 2, '0', STR_PAD_LEFT);
        $minuteValue = str_pad($slot->minute, 2, '0', STR_PAD_LEFT);
        if (!isset($availableTimes[$hourKey])) {
          $availableTimes[$hourKey] = [];
        }
        $availableTimes[$hourKey][] = $minuteValue;
      }
    }

    $imagesPerChunk = 30;

    $viewData = [
      'camera' => $camera,
      'breadcrumbs' => $this->generateBreadcrumbs($camera, $date, $hour, $minute, $chunk),
      'filter' => [
        'date' => $date,
        'hour' => $hour,
        'minute' => $minute,
        'chunk' => $chunk,
      ],
      'availableTimes' => $availableTimes, // BARU: Kirim data ke view
    ];

    $query = ImageRecord::where('camera_id', $camera->id);

    if ($date && $hour && $minute) {
      $minuteQuery = $query->clone()->whereDate('captured_at', $date)
        ->where(DB::raw('HOUR(captured_at)'), $hour)
        ->where(DB::raw('MINUTE(captured_at)'), $minute);
      $totalImagesInMinute = $minuteQuery->count();
      if ($chunk || $totalImagesInMinute <= $imagesPerChunk) {
        $viewData['level'] = 'gallery';
        $skip = $chunk ? ($chunk - 1) * $imagesPerChunk : 0;
        $images = $minuteQuery->orderBy('captured_at', 'asc')->skip($skip)->take($imagesPerChunk)->get()->map(function ($image) {
          return [
            'url' => Storage::url($image->path),
            'time' => Carbon::parse($image->captured_at)->format('H:i:s'),
            'name' => basename($image->path),
          ];
        });
        $viewData['items'] = $images;
      } else {
        $viewData['level'] = 'chunk';
        $numberOfChunks = ceil($totalImagesInMinute / $imagesPerChunk);
        $chunks = [];
        for ($i = 1; $i <= $numberOfChunks; $i++) {
          $startRange = ($i - 1) * $imagesPerChunk + 1;
          $endRange = min($i * $imagesPerChunk, $totalImagesInMinute);
          $imageCountInThisChunk = ($endRange - $startRange) + 1;
          $chunks[] = [
            'name' => "Rekaman $startRange - $endRange",
            'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $date, 'hour' => $hour, 'minute' => $minute, 'chunk' => $i]),
            'count' => $imageCountInThisChunk,
          ];
        }
        $viewData['items'] = $chunks;
      }
    } elseif ($date && $hour) {
      $viewData['level'] = 'minute';
      $query->whereDate('captured_at', $date)->where(DB::raw('HOUR(captured_at)'), $hour);
      $minutes = $query->select(DB::raw('MINUTE(captured_at) as minute'), DB::raw('count(*) as count'))->groupBy('minute')->orderBy('minute', 'desc')->get()->map(function ($item) use ($camera, $date, $hour) {
        return ['name' => 'Menit ' . str_pad($item->minute, 2, '0', STR_PAD_LEFT), 'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $date, 'hour' => $hour, 'minute' => str_pad($item->minute, 2, '0', STR_PAD_LEFT)]), 'count' => $item->count];
      });
      $viewData['items'] = $minutes;
    } elseif ($date) {
      $viewData['level'] = 'hour';
      $query->whereDate('captured_at', $date);
      $hours = $query->select(DB::raw('HOUR(captured_at) as hour'), DB::raw('count(*) as count'))->groupBy('hour')->orderBy('hour', 'desc')->get()->map(function ($item) use ($camera, $date) {
        return ['name' => 'Jam ' . str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00', 'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $date, 'hour' => str_pad($item->hour, 2, '0', STR_PAD_LEFT)]), 'count' => $item->count];
      });
      $viewData['items'] = $hours;
    } else {
      $viewData['level'] = 'date';
      $dates = $query->select(DB::raw('DATE(captured_at) as date'), DB::raw('count(*) as count'))->groupBy('date')->orderBy('date', 'desc')->paginate(30);
      $dates->getCollection()->transform(function ($item) use ($camera) {
        return ['name' => Carbon::parse($item->date)->translatedFormat('l, j F Y'), 'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $item->date]), 'count' => $item->count, 'raw_date' => $item->date];
      });
      $viewData['items'] = $dates;
    }

    return view('content.pages.Log.Riwayat_Rekaman_Explorer', $viewData);
  }

  /**
   * Helper function untuk membuat breadcrumbs navigasi.
   */
  private function generateBreadcrumbs(Camera $camera, $date, $hour, $minute, $chunk)
  {
    $breadcrumbs = [['name' => $camera->name, 'url' => route('log.history.explorer', $camera->id)]];
    if ($date) {
      $breadcrumbs[] = ['name' => Carbon::parse($date)->translatedFormat('j M Y'), 'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $date])];
    }
    if ($date && $hour) {
      $breadcrumbs[] = ['name' => 'Jam ' . $hour . ':00', 'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $date, 'hour' => $hour])];
    }
    if ($date && $hour && $minute) {
      $breadcrumbs[] = ['name' => 'Menit ' . $minute, 'url' => route('log.history.explorer', ['camera' => $camera->id, 'date' => $date, 'hour' => $hour, 'minute' => $minute])];
    }
    if ($date && $hour && $minute && $chunk) {
      $breadcrumbs[] = ['name' => 'Grup Rekaman', 'url' => null];
    }
    return $breadcrumbs;
  }

  /**
   * Menghapus semua rekaman & file pada tanggal tertentu.
   */
  public function destroyFolder(Request $request, Camera $camera)
  {
    $this->authorize('delete', $camera);
    $dateToDelete = $request->input('date');
    if (!$dateToDelete) {
      return back()->with('error', 'Tanggal tidak valid.');
    }
    try {
      $formattedDate = Carbon::parse($dateToDelete)->format('Y-m-d');
    } catch (\Exception $e) {
      return back()->with('error', 'Format tanggal tidak valid.');
    }
    ImageRecord::where('camera_id', $camera->id)->whereDate('captured_at', $formattedDate)->delete();
    $directory = "camera_images/{$camera->device_id}/{$formattedDate}";
    Storage::disk('public')->deleteDirectory($directory);
    return redirect()->route('log.history.explorer', $camera->id)->with('success', 'Semua rekaman untuk tanggal ' . $formattedDate . ' berhasil dihapus.');
  }
}
