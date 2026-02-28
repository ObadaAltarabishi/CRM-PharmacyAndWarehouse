<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ExpenseInvoiceController;
use App\Http\Controllers\PharmacyAuthController;
use App\Http\Controllers\PharmacyInventoryController;
use App\Http\Controllers\PharmacyOrderController;
use App\Http\Controllers\PharmacyOrderCartController;
use App\Http\Controllers\PharmacySalesCartController;
use App\Http\Controllers\PharmacyStatsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\WarehouseAuthController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseInventoryController;
use App\Http\Controllers\WarehouseOrderController;
use App\Http\Controllers\WarehouseStatsController;
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
    Route::get('/pharmacies', [PharmacyController::class, 'index']);
    Route::get('/pharmacies/count', [PharmacyController::class, 'count']);
    Route::get('/regions/pharmacies-count', [PharmacyController::class, 'countsByRegion']);
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::get('/warehouses/count', [WarehouseController::class, 'count']);
    Route::get('/regions/warehouses-count', [WarehouseController::class, 'countsByRegion']);
    Route::get('/feedbacks', [FeedbackController::class, 'index']);
    Route::get('/feedbacks/{feedback}', [FeedbackController::class, 'show']);

    Route::delete('/admins/{admin}', [AdminController::class, 'destroy']);
    Route::delete('/pharmacies/{pharmacy}', [PharmacyController::class, 'destroy']);
    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

    Route::post('/pharmacies', [PharmacyController::class, 'store']);
    Route::post('/warehouses', [WarehouseController::class, 'store']);

    Route::patch('/admins/{admin}/make-super-admin', [AdminController::class, 'makeSuperAdmin']);
});



Route::middleware(['auth:sanctum', 'abilities:pharmacy'])->group(function () {
    Route::post('/pharmacy/feedback', [FeedbackController::class, 'storeFromPharmacy']);
    Route::get('/pharmacy/products', [PharmacyInventoryController::class, 'index']);
    Route::post('/pharmacy/products', [PharmacyInventoryController::class, 'store']);
    Route::delete('/pharmacy/products/{barcode}', [PharmacyInventoryController::class, 'destroy']);
    Route::get('/pharmacy/orders', [PharmacyOrderController::class, 'index']);
    Route::post('/pharmacy/orders', [PharmacyOrderController::class, 'store']);
    Route::post('/pharmacy/orders/{order}/receive', [PharmacyOrderController::class, 'receive']);
    Route::post('/pharmacy/orders/{order}/issue', [PharmacyOrderController::class, 'issue']);
    Route::post('/pharmacy/sales', [SalesInvoiceController::class, 'store']);
    Route::get('/pharmacy/sales-invoices', [SalesInvoiceController::class, 'index']);
    Route::get('/pharmacy/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show']);
    Route::patch('/pharmacy/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'update']);
    Route::patch('/pharmacy/sales-invoices/{salesInvoice}/paid-total', [SalesInvoiceController::class, 'updatePaidTotal']);
    Route::delete('/pharmacy/sales-invoices/{salesInvoice}/feedback', [SalesInvoiceController::class, 'clearFeedback']);
    Route::get('/pharmacy/sales-cart', [PharmacySalesCartController::class, 'show']);
    Route::post('/pharmacy/sales-cart/items', [PharmacySalesCartController::class, 'addItem']);
    Route::delete('/pharmacy/sales-cart/items/{barcode}', [PharmacySalesCartController::class, 'removeItem']);
    Route::patch('/pharmacy/sales-cart/items/{barcode}', [PharmacySalesCartController::class, 'updateQuantity']);
    Route::delete('/pharmacy/sales-cart', [PharmacySalesCartController::class, 'clear']);
    Route::post('/pharmacy/sales-cart/checkout', [PharmacySalesCartController::class, 'checkout']);
    Route::post('/pharmacy/sales-cart/checkout/confirm', [PharmacySalesCartController::class, 'confirmCheckout']);
    Route::get('/pharmacy/order-cart', [PharmacyOrderCartController::class, 'show']);
    Route::post('/pharmacy/order-cart/items', [PharmacyOrderCartController::class, 'addItem']);
    Route::delete('/pharmacy/order-cart/items/{barcode}', [PharmacyOrderCartController::class, 'removeItem']);
    Route::patch('/pharmacy/order-cart/items/{barcode}', [PharmacyOrderCartController::class, 'updateQuantity']);
    Route::delete('/pharmacy/order-cart', [PharmacyOrderCartController::class, 'clear']);
    Route::post('/pharmacy/order-cart/checkout', [PharmacyOrderCartController::class, 'checkout']);
    Route::get('/pharmacy/stats/summary', [PharmacyStatsController::class, 'summary']);
    Route::get('/pharmacy/expense-invoices', [ExpenseInvoiceController::class, 'index']);
    Route::get('/pharmacy/expense-invoices/{expenseInvoice}', [ExpenseInvoiceController::class, 'show']);
    Route::post('/pharmacy/expense-invoices', [ExpenseInvoiceController::class, 'store']);
    Route::put('/pharmacy/expense-invoices/{expenseInvoice}', [ExpenseInvoiceController::class, 'update']);
    Route::delete('/pharmacy/expense-invoices/{expenseInvoice}', [ExpenseInvoiceController::class, 'destroy']);
    //Route::get('/products/barcode/{barcode}', [ProductController::class, 'showByBarcode']);
    Route::post('/products', [ProductController::class, 'store']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/products/barcode/{barcode}', [ProductController::class, 'showByBarcode']);
});




Route::middleware(['auth:sanctum', 'abilities:warehouse'])->group(function () {
    Route::post('/warehouse/feedback', [FeedbackController::class, 'storeFromWarehouse']);
    Route::get('/warehouse/products', [WarehouseInventoryController::class, 'index']);
    Route::post('/warehouse/products', [WarehouseInventoryController::class, 'store']);
    Route::delete('/warehouse/products/{barcode}', [WarehouseInventoryController::class, 'destroy']);
    Route::get('/warehouse/orders', [WarehouseOrderController::class, 'index']);
    Route::get('/warehouse/orders/pending', [WarehouseOrderController::class, 'pending']);
    Route::get('/warehouse/orders/{order}', [WarehouseOrderController::class, 'show']);
    Route::get('/warehouse/orders/issues', [WarehouseOrderController::class, 'issues']);
    Route::post('/warehouse/orders/{order}/approve', [WarehouseOrderController::class, 'approve']);
    Route::post('/warehouse/orders/{order}/reject', [WarehouseOrderController::class, 'reject']);
    Route::get('/warehouse/stats/summary', [WarehouseStatsController::class, 'summary']);
    //Route::get('/products/barcode/{barcode}', [ProductController::class, 'showByBarcode']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/warehouse/expense-invoices', [ExpenseInvoiceController::class, 'indexForWarehouse']);
    Route::get('/warehouse/expense-invoices/{expenseInvoice}', [ExpenseInvoiceController::class, 'showForWarehouse']);
    Route::post('/warehouse/expense-invoices', [ExpenseInvoiceController::class, 'storeForWarehouse']);
    Route::put('/warehouse/expense-invoices/{expenseInvoice}', [ExpenseInvoiceController::class, 'updateForWarehouse']);
    Route::delete('/warehouse/expense-invoices/{expenseInvoice}', [ExpenseInvoiceController::class, 'destroyForWarehouse']);
});

Route::get('/warehouses/{warehouseId}/products', [WarehouseInventoryController::class, 'listByWarehouse']);
