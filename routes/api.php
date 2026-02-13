<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\PharmacyAuthController;
use App\Http\Controllers\WarehouseAuthController;
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
Route::post('/pharmacy/login', [PharmacyAuthController::class, 'login']);
Route::post('/warehouse/login', [WarehouseAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/admin/me', [AdminController::class, 'profile']);
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
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
    Route::get('/feedbacks/{feedback}', [FeedbackController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'abilities:pharmacy'])->group(function () {
    Route::post('/pharmacy/feedback', [FeedbackController::class, 'storeFromPharmacy']);
});

Route::middleware(['auth:sanctum', 'abilities:warehouse'])->group(function () {
    Route::post('/warehouse/feedback', [FeedbackController::class, 'storeFromWarehouse']);
});
