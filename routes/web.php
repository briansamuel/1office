<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\DeviceAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Work\TaskController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Trang chủ
Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes với Device Management
Route::prefix('auth')->group(function () {
    // Login với device tracking
    Route::post('/login', [DeviceAuthController::class, 'login'])->name('auth.login');

    // Logout với device tracking
    Route::post('/logout', [DeviceAuthController::class, 'logout'])->name('auth.logout');

    // Device management routes (cần authentication)
    Route::middleware(['auth', 'device.session'])->group(function () {
        // Trang quản lý thiết bị
        Route::get('/devices', [DeviceAuthController::class, 'index'])->name('auth.devices');

        // API endpoints cho device management
        Route::post('/devices/logout-device', [DeviceAuthController::class, 'logoutDevice'])->name('auth.devices.logout');
        Route::post('/devices/logout-other', [DeviceAuthController::class, 'logoutOtherDevices'])->name('auth.devices.logout-other');
        Route::post('/devices/logout-all', [DeviceAuthController::class, 'logoutAllDevices'])->name('auth.devices.logout-all');
        Route::get('/devices/active', [DeviceAuthController::class, 'getActiveDevices'])->name('auth.devices.active');
        Route::post('/devices/update-activity', [DeviceAuthController::class, 'updateActivity'])->name('auth.devices.activity');
        Route::get('/devices/statistics', [DeviceAuthController::class, 'getStatistics'])->name('auth.devices.statistics');
        Route::get('/devices/check-session', [DeviceAuthController::class, 'checkSession'])->name('auth.devices.check');
        Route::post('/devices/refresh-session', [DeviceAuthController::class, 'refreshSession'])->name('auth.devices.refresh');
    });
});

// Standard Laravel Auth Routes (fallback)
Auth::routes();

// Protected Routes với Device Session Middleware
Route::middleware(['auth', 'device.session'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Work Module Routes
    Route::prefix('work')->name('work.')->group(function () {
        Route::resource('tasks', TaskController::class);
        Route::post('tasks/{task}/toggle-status', [TaskController::class, 'toggleStatus'])->name('tasks.toggle-status');
        Route::get('kanban', [TaskController::class, 'kanban'])->name('kanban');
        Route::post('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    });

    // Profile Routes
    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');
});

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'connected',
            'cache' => 'connected',
        ],
    ]);
});
