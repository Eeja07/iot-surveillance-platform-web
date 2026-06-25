<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CameraTelemetryController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\LatestImageController;
use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\ImageHistoryController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\UserCameraGroupApiController;
use App\Http\Controllers\Api\UserCameraApiController;
use App\Http\Controllers\Api\EmqxWebSocketController;
use App\Http\Controllers\Api\MqttAuthController;
use App\Http\Controllers\Api\MqttWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- 1. Rute Otentikasi Publik ---
Route::post('/register', [AuthController::class, 'register']);
// PERBAIKAN: Disamakan dengan endpoint yang dipanggil Android/Python Simulation
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);

// --- 2. Alur Lupa Password (3 Langkah) ---
Route::prefix('password')->group(function () {
    // Langkah 1: Kirim OTP ke email
    Route::post('/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:3,1');

    // Langkah 2: Verifikasi OTP (Tanpa Reset)
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
        ->middleware('throttle:5,1');

    // Langkah 3: Eksekusi Reset Password
    Route::post('/reset', [ForgotPasswordController::class, 'reset']);
});

// --- 3. Rute Terproteksi (auth:sanctum) ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile Management
    Route::get('/profile', [ProfileApiController::class, 'show']);
    Route::patch('/profile', [ProfileApiController::class, 'updateProfile']);
    Route::put('/password', [ProfileApiController::class, 'updatePassword']);
    Route::delete('/profile', [ProfileApiController::class, 'destroy']);

    // Camera & Image Management
    Route::get('/cameras/{camera}/latest-image', LatestImageController::class);
    Route::get('/images/{camera}/history', [ImageHistoryController::class, 'historyExplorer']);

    // Perbaikan: Rename diletakkan di sini agar tidak 401 (Unauthorized)
    Route::put('/images/{imageRecord}/rename', [ImageHistoryController::class, 'rename']);

    // User Camera CRUD
    Route::prefix('user/cameras')->group(function () {
        Route::get('/', [UserCameraApiController::class, 'index']);
        Route::post('/', [UserCameraApiController::class, 'store']);
        Route::get('/{id}', [UserCameraApiController::class, 'show']);
        Route::put('/{id}', [UserCameraApiController::class, 'update']);
        Route::delete('/{id}', [UserCameraApiController::class, 'destroy']);
    });

    // User Camera Groups
    Route::prefix('user/camera-groups')->group(function () {
        Route::get('/', [UserCameraGroupApiController::class, 'index']);
        Route::post('/', [UserCameraGroupApiController::class, 'store']);
        Route::post('/update', [UserCameraGroupApiController::class, 'update']);
        Route::post('/delete', [UserCameraGroupApiController::class, 'destroy']);
        Route::post('/assign', [UserCameraGroupApiController::class, 'assignCamera']);
        Route::post('/remove', [UserCameraGroupApiController::class, 'removeCamera']);
    });
});

// --- 4. Rute IoT & Webhook (Tanpa Auth Sanctum biasanya) ---
Route::post('/camera/upload', [ImageUploadController::class, 'store']);
Route::post('/heartbeat', HeartbeatController::class);
Route::post('/camera/upload', [ImageUploadController::class, 'store']);
Route::post('/heartbeat', HeartbeatController::class);
Route::post('/camera-telemetry', [CameraTelemetryController::class, 'store']);


// MQTT & EMQX Bridge
Route::prefix('mqtt')->group(function () {
    Route::post('/auth', [MqttAuthController::class, 'auth'])->name('api.mqtt.auth');
    Route::post('/acl', [MqttAuthController::class, 'acl'])->name('api.mqtt.acl');
    Route::post('/webhook', [MqttWebhookController::class, 'handle'])->name('api.mqtt.webhook');
});

Route::prefix('ws-bridge')->group(function () {
    Route::post('/telemetry', [EmqxWebSocketController::class, 'handleTelemetry'])->name('api.ws.telemetry');
    Route::post('/image', [EmqxWebSocketController::class, 'handleImage'])->name('api.ws.image');
});

// Status & Sync Utils
Route::get('/camera-statuses', function() {
    return \App\Models\Camera::with(['latestTelemetry'])->get()->mapWithKeys(function ($camera) {
        $telemetry = $camera->latestTelemetry;
        return [$camera->id => [
            'is_active' => $camera->is_active,
            'mqtt_status' => $camera->mqtt_status ?? 'offline',
            'health_status' => $camera->operational_status,
            'freshness' => $camera->freshness_indicator,
            'rssi' => $telemetry ? $telemetry->formatted_rssi : 'N/A',
            'heap' => $telemetry ? $telemetry->formatted_heap : 'N/A',
            'publish_ms' => $telemetry ? $telemetry->formatted_publish : 'N/A',
            'mqtt_connected' => $telemetry ? $telemetry->mqtt_status_text : 'N/A',
            'ws_connected' => $telemetry ? $telemetry->ws_status_text : 'N/A',
            'mqtt_reconnect' => $telemetry ? $telemetry->reconnect_delta_text : '+0',
            'ws_close_count' => $telemetry ? $telemetry->ws_close_delta_text : '+0',
            'publish_fail' => $telemetry ? $telemetry->publish_fail_delta_text : '+0',
            'uptime' => $telemetry ? $telemetry->formatted_uptime : 'N/A',
        ]];
    });
});

//Route::get('/mqtt/sync', function(\App\Services\EmqxService $emqx) {
  //  try {
    //    $emqx->setupAuthentication();
      //  $emqx->setupAuthorization();
       // $emqx->setupImageRule();
       // return response()->json(['message' => 'Sync Successful!']);
   // } catch (\Exception $e) {
     //   return response()->json(['error' => $e->getMessage()], 500);
    //}
//});
Route::get('/mqtt/sync', function(\App\Services\EmqxService $emqx) {
    try {
        $emqx->syncAll();
        return response()->json(['message' => 'Sync Successful!']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
