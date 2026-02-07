<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Admin;
use App\Models\Reagion;
use App\Models\Pharmacy;
use App\Http\Controllers\PharmacyController;
use App\Models\Warehouse;




Route::post('/admin/register', [AdminAuthController::class, 'register']);
Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admins', [AdminController::class, 'index']);
    Route::get('/admins/count', [AdminController::class, 'count']);
    Route::get('/admins/pharmacies-count', [AdminController::class, 'pharmaciesCount']);
    Route::get('/admins/warehouses-count', [AdminController::class, 'warehousesCount']);
    Route::get('/regions/admins-count', [AdminController::class, 'adminsCountByRegion']);
    Route::patch('/admins/{admin}/make-super-admin', [AdminController::class, 'makeSuperAdmin']);
    Route::delete('/admins/{admin}', [AdminController::class, 'destroy']);
    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/pharmacies/count', [PharmacyController::class, 'count']);
    Route::get('/regions/pharmacies-count', [PharmacyController::class, 'countsByRegion']);
    Route::post('/pharmacies', [PharmacyController::class, 'store']);
    Route::delete('/pharmacies/{pharmacy}', [PharmacyController::class, 'destroy']);
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::get('/warehouses/count', [WarehouseController::class, 'count']);
    Route::get('/regions/warehouses-count', [WarehouseController::class, 'countsByRegion']);
    Route::post('/warehouses', [WarehouseController::class, 'store']);
    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy']);
});
