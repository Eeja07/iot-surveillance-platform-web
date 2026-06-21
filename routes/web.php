<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;

// Dashboard
use App\Http\Controllers\Pages\UserDashboardController;
use App\Http\Controllers\Pages\DashboardController;
use App\Http\Controllers\Pages\CameraGroupController;
// --- Controllers Modul Admin (Pages/Admin/) ---
use App\Http\Controllers\Pages\Admin\ManajemenKameraController;
use App\Http\Controllers\Pages\Admin\NotifikasiPeringatanController;
// --- Controllers Modul Report (Pages/Log/) ---
use App\Http\Controllers\Pages\Log\LogAktifitasController;
use App\Http\Controllers\Pages\Log\RiwayatRekamanController;
// --- Controllers Modul Invoice/Bill (Pages/ML/) ---
use App\Http\Controllers\Pages\ML\LogDeteksiMlController;
// --- Controllers Modul Setting (Pages/Setting/) ---
use App\Http\Controllers\Pages\Setting\UserController;
use App\Http\Controllers\Pages\Setting\RoleController;
// --- Controllers Modul User (Pages/User/) ---
use App\Http\Controllers\Pages\User\UserCameraLinkController;
use App\Http\Controllers\Pages\User\UserManajemenKameraController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Middleware 'auth' dan 'verified' digabung untuk rute-rute utama
Route::middleware(['auth', 'verified'])->group(function () {

    // 1. Redirection Utama (Dashboard Gateway)
    Route::get('/', function () {
        return auth()->user()->hasRole('admin')
            ? redirect()->route('dashboard.index')
            : redirect()->route('user.dashboard');
    });

    Route::get('/dashboard', function () {
        return auth()->user()->hasRole('admin')
            ? redirect()->route('dashboard.index')
            : redirect()->route('user.dashboard');
    })->name('dashboard');

    // 2. Grup Utama Dashboard (Prefix /dashboard)
    Route::prefix('dashboard')->group(function () {

        // --- ADMIN AREA ---
        // Dashboard Admin
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::post('/groups', [DashboardController::class, 'updateGroups'])->name('dashboard.groups');

        // Admin Management
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('cameras', ManajemenKameraController::class);
            Route::get('cameras/{camera}/qrcode', [ManajemenKameraController::class, 'downloadQrCode'])->name('cameras.qrcode');
            Route::get('/notifications', [NotifikasiPeringatanController::class, 'index'])->name('notifications.index');

            Route::prefix('camera-groups')->name('camera-groups.')->group(function () {
                Route::get('/', [CameraGroupController::class, 'index'])->name('index');
                Route::post('/', [CameraGroupController::class, 'store'])->name('store');
                Route::put('/{groupName}', [CameraGroupController::class, 'update'])->name('update');
                Route::delete('/{groupName}', [CameraGroupController::class, 'destroy'])->name('destroy');
                Route::post('/assign', [CameraGroupController::class, 'assignCamera'])->name('assign');
                Route::post('/remove', [CameraGroupController::class, 'removeCamera'])->name('remove');
            });
        });

        // ML Logs
        Route::prefix('ml')->name('ml.')->group(function () {
            Route::get('/detection-log', [LogDeteksiMlController::class, 'index'])->name('detection-log.index');
        });

        // System Logs & History
        Route::prefix('log')->name('log.')->group(function () {
            Route::get('/activities', [LogAktifitasController::class, 'index'])->name('activities.index');
            Route::get('/history', [RiwayatRekamanController::class, 'index'])->name('history.index');
            Route::get('/history/kamera/{camera}/{date?}/{hour?}/{minute?}/{chunk?}', [RiwayatRekamanController::class, 'showExplorer'])
                ->name('history.explorer')
                ->where([
                    'date'   => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
                    'hour'   => '[0-9]{2}',
                    'minute' => '[0-9]{2}',
                    'chunk'  => '[0-9]+',
                ]);
            Route::delete('/history/kamera/{camera}/hapus-folder', [RiwayatRekamanController::class, 'destroyFolder'])->name('history.destroy.folder');
        });

        // Settings (Users & Roles)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::resource('roles', RoleController::class);
            Route::resource('users', UserController::class);
        });

        // --- USER AREA ---
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
            Route::post('/groups', [UserDashboardController::class, 'updateGroups'])->name('dashboard.groups');

            // User Camera Groups
            Route::prefix('camera-groups')->name('camera-groups.')->group(function () {
                Route::get('/', [CameraGroupController::class, 'index'])->name('index');
                Route::post('/', [CameraGroupController::class, 'store'])->name('store');
                Route::put('/{groupName}', [CameraGroupController::class, 'update'])->name('update');
                Route::delete('/{groupName}', [CameraGroupController::class, 'destroy'])->name('destroy');
                Route::post('/assign', [CameraGroupController::class, 'assignCamera'])->name('assign');
                Route::post('/remove', [CameraGroupController::class, 'removeCamera'])->name('remove');
            });

            // User Camera Linking
            Route::prefix('cameras')->name('cameras.')->group(function () {
                Route::get('/link', [UserCameraLinkController::class, 'create'])->name('link.create');
                Route::post('/link', [UserCameraLinkController::class, 'store'])->name('link.store');
            });

            Route::get('/my-cameras/data', [UserManajemenKameraController::class, 'getData'])->name('my-cameras.data');
            Route::resource('my-cameras', UserManajemenKameraController::class)->except(['create', 'store', 'show']);
        });
    });

    // 3. API Internal Terproteksi
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/camera-statuses', [ApiController::class, 'getCameraStatuses'])->name('statuses');
    });

    // 4. Profil Pengguna
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rute Utilitas/Testing (Tanpa Verified mungkin diperlukan)
Route::get('/test-reverb', function () {
    return view('test-reverb');
});

require __DIR__ . '/auth.php';
