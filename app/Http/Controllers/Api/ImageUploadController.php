<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Camera;
use App\Models\ImageRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        Log::info('--- [API UPLOAD START] ---');

        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|exists:cameras,device_id',
            'api_key'   => 'required|string',
            'image'     => 'required|image|mimes:jpeg,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $camera = Camera::where('device_id', $request->device_id)->first();

        if (!$camera || !hash_equals($camera->api_key, $request->api_key)) {
            return response()->json(['message' => 'Unauthorized: Invalid device_id or api_key.'], 401);
        }

        try {
            $dateFolder = now()->format('Y-m-d');
            $filename = now()->format('His') . '_' . uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
            $directory = "camera_images/{$camera->device_id}/{$dateFolder}";
            $path = $request->file('image')->storeAs($directory, $filename, 's3');

            if (!$path) {
                throw new \Exception("Driver S3 gagal mengembalikan path.");
            }
        } catch (\Exception $e) {
            Log::error('!!! MINIO STORAGE FAILED !!!', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not store image to MinIO.'], 500);
        }

        try {
            $imageRecord = $camera->imageRecords()->create([
                'path'        => $path,
                'captured_at' => now(),
            ]);

            // SOLUSI JANGKA PANJANG: Update kolom latest_image di tabel cameras
            $camera->update([
                'last_heartbeat_at' => now(),
                'latest_image_path' => $path,
                'latest_image_at'   => now(),
            ]);

            Log::info('SUCCESS: Image uploaded and latest_image updated.');

            broadcast(new \App\Events\NewImageReceived($camera, $imageRecord));
        } catch (\Exception $e) {
            Log::error('!!! DATABASE INSERT FAILED !!!', ['error' => $e->getMessage()]);
            Storage::disk('s3')->delete($path);
            return response()->json(['message' => 'Could not save image record.'], 500);
        }

        return response()->json(['message' => 'Image uploaded successfully'], 201);
    }
}
