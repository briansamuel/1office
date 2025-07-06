<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Work Module Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('work')->name('work.')->group(function () {
    // Task routes
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::post('/', [TaskController::class, 'store'])->name('store');
        Route::get('/statistics', [TaskController::class, 'statistics'])->name('statistics');
        Route::get('/kanban', [TaskController::class, 'kanban'])->name('kanban');
        Route::get('/{id}', [TaskController::class, 'show'])->name('show');
        Route::put('/{id}', [TaskController::class, 'update'])->name('update');
        Route::delete('/{id}', [TaskController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/status', [TaskController::class, 'updateStatus'])->name('update-status');
        Route::patch('/{id}/assign', [TaskController::class, 'assign'])->name('assign');
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
        // TODO: Implement employee routes
    });
    
    // Attendance routes
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // TODO: Implement attendance routes
    });
    
    // Leave routes
    Route::prefix('leaves')->name('leaves.')->group(function () {
        // TODO: Implement leave routes
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
        // TODO: Implement customer routes
    });
    
    // Lead routes
    Route::prefix('leads')->name('leads.')->group(function () {
        // TODO: Implement lead routes
    });
    
    // Deal routes
    Route::prefix('deals')->name('deals.')->group(function () {
        // TODO: Implement deal routes
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
        // TODO: Implement product routes
    });
    
    // Inventory routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // TODO: Implement inventory routes
    });
    
    // Order routes
    Route::prefix('orders')->name('orders.')->group(function () {
        // TODO: Implement order routes
    });
});
