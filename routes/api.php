<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Controllers\Api\DeviceManagementController;
use App\Http\Controllers\Api\PostmanController;
use App\Http\Controllers\Api\Work\TaskController as ApiTaskController;
use App\Http\Controllers\Api\AuthController;
use App\Modules\Work\Controllers\TaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Public authentication routes
    Route::post('/register', [DeviceAuthController::class, 'register']);
    Route::post('/login', [DeviceAuthController::class, 'login']);
    Route::post('/forgot-password', [DeviceAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [DeviceAuthController::class, 'resetPassword']);

    // Protected authentication routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [DeviceAuthController::class, 'me']);
        Route::post('/logout', [DeviceAuthController::class, 'logout']);
        Route::post('/refresh', [DeviceAuthController::class, 'refresh']);
    });
});

/*
|--------------------------------------------------------------------------
| Device Management Routes
|--------------------------------------------------------------------------
*/

Route::prefix('devices')->middleware('auth:sanctum')->group(function () {
    // Device listing and information
    Route::get('/', [DeviceManagementController::class, 'getActiveDevices']);
    Route::get('/current', [DeviceManagementController::class, 'getCurrentDevice']);
    Route::get('/statistics', [DeviceManagementController::class, 'getStatistics']);

    // Device management actions
    Route::post('/logout/{sessionToken}', [DeviceManagementController::class, 'logoutDevice']);
    Route::post('/logout-other', [DeviceManagementController::class, 'logoutOtherDevices']);
    Route::post('/logout-all', [DeviceManagementController::class, 'logoutAllDevices']);
    Route::put('/name', [DeviceManagementController::class, 'setDeviceName']);

    // Session management
    Route::post('/activity', [DeviceManagementController::class, 'updateActivity']);
    Route::get('/session/check', [DeviceManagementController::class, 'checkSession']);
    Route::post('/session/refresh', [DeviceManagementController::class, 'refreshSession']);
});

/*
|--------------------------------------------------------------------------
| Work Module Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('work')->name('work.')->group(function () {
    // Task routes
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])
            ->middleware('permission:work.tasks.read')
            ->name('index');

        Route::post('/', [TaskController::class, 'store'])
            ->middleware('permission:work.tasks.create')
            ->name('store');

        Route::get('/statistics', [TaskController::class, 'statistics'])
            ->middleware('permission:work.tasks.read')
            ->name('statistics');

        Route::get('/kanban', [TaskController::class, 'kanban'])
            ->middleware('permission:work.tasks.read')
            ->name('kanban');

        Route::get('/{id}', [TaskController::class, 'show'])
            ->middleware('permission:work.tasks.read')
            ->name('show');

        Route::put('/{id}', [TaskController::class, 'update'])
            ->middleware('permission:work.tasks.update')
            ->name('update');

        Route::delete('/{id}', [TaskController::class, 'destroy'])
            ->middleware('permission:work.tasks.delete')
            ->name('destroy');

        Route::patch('/{id}/status', [TaskController::class, 'updateStatus'])
            ->middleware('permission:work.tasks.update')
            ->name('update-status');

        Route::patch('/{id}/assign', [TaskController::class, 'assign'])
            ->middleware('permission:work.tasks.update')
            ->name('assign');
    });
});

/*
|--------------------------------------------------------------------------
| System Administration Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    // User management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'User management endpoint - TODO']);
        })->name('index');
    });

    // Role management
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Role management endpoint - TODO']);
        })->name('index');
    });

    // Permission management
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Permission management endpoint - TODO']);
        })->name('index');
    });

    // Organization management
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Organization management endpoint - TODO']);
        })->name('index');
    });

    // Audit logs
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Audit logs endpoint - TODO']);
        })->name('index');
    });
});

/*
|--------------------------------------------------------------------------
| HRM Module Routes (Placeholder)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('hrm')->name('hrm.')->group(function () {
    // Employee routes
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'HRM employees endpoint - TODO']);
        })->middleware('permission:hrm.employees.read')->name('index');
    });

    // Attendance routes
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'HRM attendance endpoint - TODO']);
        })->middleware('permission:hrm.attendance.manage')->name('index');
    });
});

/*
|--------------------------------------------------------------------------
| CRM Module Routes (Placeholder)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('crm')->name('crm.')->group(function () {
    // Customer routes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'CRM customers endpoint - TODO']);
        })->middleware('permission:crm.customers.read')->name('index');
    });

    // Lead routes
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'CRM leads endpoint - TODO']);
        })->middleware('permission:crm.leads.manage')->name('index');
    });
});

/*
|--------------------------------------------------------------------------
| Warehouse Module Routes (Placeholder)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('warehouse')->name('warehouse.')->group(function () {
    // Product routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Warehouse products endpoint - TODO']);
        })->middleware('permission:warehouse.products.read')->name('index');
    });

    // Inventory routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', function () {
            return response()->json(['message' => 'Warehouse inventory endpoint - TODO']);
        })->middleware('permission:warehouse.inventory.manage')->name('index');
    });
});

/*
|--------------------------------------------------------------------------
| API Documentation Routes
|--------------------------------------------------------------------------
*/

Route::get('/documentation', function () {
    return response()->json([
        'message' => 'API Documentation',
        'postman_collection' => url('/api/postman-collection'),
        'postman_environment' => url('/api/postman-environment'),
        'authentication_docs' => 'See API_AUTHENTICATION_DOCUMENTATION.md',
        'endpoints' => [
            'authentication' => url('/api/auth'),
            'device_management' => url('/api/devices'),
            'work_module' => url('/api/work'),
            'user_profile' => url('/api/user')
        ]
    ]);
});

Route::get('/postman-collection', [PostmanController::class, 'collection']);
Route::get('/postman-environment', [PostmanController::class, 'environment']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
