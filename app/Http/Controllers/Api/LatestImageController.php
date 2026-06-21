<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class LatestImageController extends Controller
{
    /**
     * SOLUSI JANGKA PANJANG:
     * Tidak query image_records sama sekali.
     * Langsung ambil dari kolom latest_image_path di tabel cameras.
     * 1 query ringan ke tabel cameras yang sudah pasti pakai index.
     */
    public function __invoke(Camera $camera)
    {
        if ($camera->latest_image_path) {
            return response()->json([
                'success'     => true,
                'image_url'   => Storage::disk('s3')->url($camera->latest_image_path),
                'captured_at' => $camera->latest_image_at
                    ? $camera->latest_image_at->format('H:i:s') . ' WIB'
                    : 'N/A',
            ]);
        }

        return response()->json([
            'success'     => false,
            'image_url'   => 'https://placehold.co/600x400/293445/FFFFFF?text=No+Image',
            'captured_at' => 'N/A',
        ]);
    }
}
