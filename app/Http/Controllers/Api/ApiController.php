<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    /**
     * Mengambil status terbaru dari semua kamera milik pengguna yang sedang login.
     * Status ini didasarkan pada accessor 'is_active' di model Camera.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCameraStatuses()
    {
        $cameras = Auth::user()->cameras;

        $statuses = $cameras->mapWithKeys(function ($camera) {
            return [
                $camera->id => $camera->is_active // Di sini accessor getIsActiveAttribute() bekerja
            ];
        });

        return response()->json($statuses);
    }
}
