<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HeartbeatController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            // Kita bisa mengganti validasi agar lebih sesuai, misalnya dengan device_id
            $validated = $request->validate([
                'device_id' => 'required|string|exists:cameras,device_id',
            ]);

            $camera = Camera::where('device_id', $validated['device_id'])->firstOrFail();

            // HANYA UPDATE TIMESTAMP
            $camera->last_heartbeat_at = now();
            $camera->save();

            // Hapus event WebSocket yang tidak lagi digunakan
            // event(new CameraOnline($camera));

            Log::info('Heartbeat received for camera: ' . $camera->name);

            return response()->json(['status' => 'ok']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Heartbeat error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Camera not found or internal error.'], 404);
        }
    }
}
