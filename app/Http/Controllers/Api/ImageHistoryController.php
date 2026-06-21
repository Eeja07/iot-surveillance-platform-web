<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\ImageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImageHistoryController extends Controller
{
    /**
     * Mengambil riwayat rekaman gambar (image records) berdasarkan filter waktu.
     * Endpoint: GET /api/images/{camera}/history
     * Query Params: date=YYYY-MM-DD, hour=HH, minute=MM, chunk=N
     *
     * PERBAIKAN: Tambah reorder() di awal query utama agar tidak ada
     * orderBy dari relasi imageRecords() yang menyebabkan window function hang
     */
    public function historyExplorer(Request $request, Camera $camera)
    {
        // 1. Otorisasi
        if ($request->user()->id !== $camera->user_id) {
            return response()->json(['message' => 'Forbidden: You do not own this camera.'], 403);
        }

        $date = $request->query('date');
        $hour = $request->query('hour');
        $minute = $request->query('minute');
        $chunk = $request->query('chunk');
        $imagesPerChunk = 30;

        // PERBAIKAN: reorder() di awal untuk buang orderBy dari relasi
        $query = $camera->imageRecords()->reorder();
        $level = 'date';

        // 2. Query Berdasarkan Filter
        if ($date) {
            try {
                $date = Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid date format.'], 400);
            }
            $query->whereDate('captured_at', $date);

            if ($hour) {
                $query->where(DB::raw('HOUR(captured_at)'), $hour);

                if ($minute) {
                    $query->where(DB::raw('MINUTE(captured_at)'), $minute);
                    $level = 'gallery';
                } else {
                    $level = 'minute';
                }
            } else {
                $level = 'hour';
            }
        }

        // 3. Pemrosesan dan Pengembalian Data Berdasarkan Level
        $response = [
            'level' => $level,
            'camera_id' => $camera->id,
            'filter' => ['date' => $date, 'hour' => $hour, 'minute' => $minute, 'chunk' => $chunk],
            'items' => [],
            'pagination' => null,
        ];

        switch ($level) {
            case 'gallery':
                $minuteQuery = clone $query;
                $totalImagesInMinute = $minuteQuery->count();

                if ($totalImagesInMinute > $imagesPerChunk && !$chunk) {
                    $level = 'chunk';
                } else {
                    $skip = $chunk ? ($chunk - 1) * $imagesPerChunk : 0;
                    $images = $minuteQuery
                        ->orderBy('captured_at', 'asc')
                        ->skip($skip)->take($imagesPerChunk)->get()->map(function ($image) {
                            return [
                                'id' => $image->id,
                                'file_name' => $image->name ?? basename($image->path),
                                'url' => Storage::url($image->path),
                                'captured_at' => Carbon::parse($image->captured_at)->toDateTimeString(),
                            ];
                        });
                    $response['items'] = $images;
                    break;
                }
                // Fallthrough ke 'chunk' jika diperlukan

            case 'chunk':
                $totalImagesInMinute = $query->count();
                $numberOfChunks = ceil($totalImagesInMinute / $imagesPerChunk);
                $chunks = [];
                for ($i = 1; $i <= $numberOfChunks; $i++) {
                    $startRange = ($i - 1) * $imagesPerChunk + 1;
                    $endRange = min($i * $imagesPerChunk, $totalImagesInMinute);
                    $chunks[] = [
                        'type' => 'chunk',
                        'name' => "Rekaman $startRange - $endRange",
                        'count' => ($endRange - $startRange) + 1,
                        'chunk_number' => $i,
                    ];
                }
                $response['level'] = 'chunk';
                $response['items'] = $chunks;
                break;

            case 'minute':
                $minutes = $query->select(
                        DB::raw('MINUTE(captured_at) as minute'),
                        DB::raw('count(*) as count')
                    )
                    ->groupBy('minute')
                    ->orderBy('minute', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'type' => 'minute',
                            'name' => 'Menit ' . str_pad($item->minute, 2, '0', STR_PAD_LEFT),
                            'count' => $item->count,
                            'minute_raw' => str_pad($item->minute, 2, '0', STR_PAD_LEFT),
                        ];
                    });
                $response['items'] = $minutes;
                break;

            case 'hour':
                $hours = $query->select(
                        DB::raw('HOUR(captured_at) as hour'),
                        DB::raw('count(*) as count')
                    )
                    ->groupBy('hour')
                    ->orderBy('hour', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'type' => 'hour',
                            'name' => 'Jam ' . str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
                            'count' => $item->count,
                            'hour_raw' => str_pad($item->hour, 2, '0', STR_PAD_LEFT),
                        ];
                    });
                $response['items'] = $hours;
                break;

            case 'date':
                $dates = $query->select(
                        DB::raw('DATE(captured_at) as date'),
                        DB::raw('count(*) as count')
                    )
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->paginate(30);

                $dates->getCollection()->transform(function ($item) {
                    return [
                        'type' => 'date',
                        'name' => Carbon::parse($item->date)->translatedFormat('l, j F Y'),
                        'count' => $item->count,
                        'date_raw' => $item->date,
                    ];
                });

                $response['items'] = $dates->items();
                $response['pagination'] = [
                    'total' => $dates->total(),
                    'per_page' => $dates->perPage(),
                    'current_page' => $dates->currentPage(),
                    'last_page' => $dates->lastPage(),
                    'next_page_url' => $dates->nextPageUrl(),
                ];
                break;
        }

        return response()->json($response, 200);
    }

    /**
     * Mengganti nama file gambar yang sudah diunggah.
     * Endpoint: PUT /api/images/{id}/rename
     */
    public function rename(Request $request, $id)
    {
        if (is_null($request->user())) {
            return response()->json(['message' => 'Unauthenticated: User token is invalid or missing.'], 401);
        }

        $imageRecord = ImageRecord::findOrFail($id);
        $imageRecord->loadMissing('camera');

        if (is_null($imageRecord->camera)) {
            Log::error('RENAME FAILED: ImageRecord ID ' . $imageRecord->id . ' has no associated Camera.');
            return response()->json(['message' => 'Record error: Associated camera not found.'], 409);
        }

        if ($request->user()->id !== $imageRecord->camera->user_id) {
            return response()->json(['message' => 'Forbidden: You do not own this image.'], 403);
        }

        $request->validate([
            'new_name' => 'required|string|max:255',
        ]);

        $oldPath = $imageRecord->path;
        $newName = $request->new_name;
        $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
        $oldDirectory = pathinfo($oldPath, PATHINFO_DIRNAME);
        $sanitizedNewName = Str::slug($newName, '_');
        $newFilename = $sanitizedNewName . '_' . $imageRecord->id . '.' . $extension;
        $newPath = $oldDirectory . '/' . $newFilename;

        Log::info("Attempting rename from: {$oldPath} to: {$newPath}");

        try {
            if (!Storage::disk('public')->exists($oldPath)) {
                Log::error("File not found at: {$oldPath}");
                return response()->json(['message' => 'Original file not found on disk.'], 404);
            }
            Storage::disk('public')->move($oldPath, $newPath);
            Log::info('SUCCESS: File renamed on disk.');
        } catch (\Exception $e) {
            Log::error('!!! FILE RENAME FAILED !!!', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not rename file.'], 500);
        }

        try {
            $imageRecord->path = $newPath;
            $imageRecord->save();
            Log::info('SUCCESS: Database path updated.');
            return response()->json([
                'message' => 'Image renamed successfully',
                'new_path' => Storage::url($newPath),
                'record_id' => $imageRecord->id
            ], 200);
        } catch (\Exception $e) {
            Log::error('!!! DB UPDATE FAILED AFTER RENAME !!!', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'File renamed, but failed to update database.'], 500);
        }
    }
}
