<?php

use App\Http\Controllers\Business\BranchController;
use App\Http\Controllers\Business\BusinessWorkspaceController;
use App\Http\Controllers\Business\CategoryController;
use App\Http\Controllers\Business\DashboardController;
use App\Http\Controllers\Business\InventoryController;
use App\Http\Controllers\Business\KitchenController;
use App\Http\Controllers\Business\OrderManagementController;
use App\Http\Controllers\Business\PosController;
use App\Http\Controllers\Business\ProductController;
use App\Http\Controllers\Business\ReportController;
use App\Http\Controllers\Business\SettingController;
use App\Http\Controllers\Business\StaffController;
use App\Http\Controllers\Business\TableController;
use Illuminate\Support\Facades\Route;

// Workspace onboarding & switching
Route::get('/create-business', [BusinessWorkspaceController::class, 'create'])->name('create');
Route::post('/create-business', [BusinessWorkspaceController::class, 'store'])->name('store');
Route::post('/switch-business/{business}', [BusinessWorkspaceController::class, 'switch'])->name('switch');

// Main Business Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Category Management
Route::resource('categories', CategoryController::class);
Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

// Product Management
Route::resource('products', ProductController::class);
Route::post('/products/{product}/toggle-availability', [ProductController::class, 'toggleAvailability'])->name('products.toggle-availability');

// Branch Management
Route::resource('branches', BranchController::class)->only(['index', 'store', 'update', 'destroy']);

// Staff Management
Route::post('/staff/transfer-ownership', [StaffController::class, 'transferOwnership'])->name('staff.transfer-ownership');
Route::resource('staff', StaffController::class)->only(['index', 'store', 'update', 'destroy']);

// Business Settings
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

// Table & QR Code Management
Route::get('/tables/master/download-png', [TableController::class, 'downloadMasterQrPng'])->name('tables.master.download-png');
Route::get('/tables/master/standee', [TableController::class, 'masterStandee'])->name('tables.master.standee');
Route::get('/tables/{table}/download-png', [TableController::class, 'downloadTableQrPng'])->name('tables.download-png');
Route::get('/tables/{table}/standee', [TableController::class, 'tableStandee'])->name('tables.standee');
Route::resource('tables', TableController::class)->only(['index', 'store', 'update', 'destroy']);

// Order Management
Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderManagementController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.update-status');
Route::post('/orders/{order}/payment', [OrderManagementController::class, 'updatePayment'])->name('orders.update-payment');
Route::get('/orders/{order}/receipt', [OrderManagementController::class, 'receipt'])->name('orders.receipt');

// Kitchen Display System (KDS)
Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
Route::get('/kitchen/api/orders', [KitchenController::class, 'ordersApi'])->name('kitchen.api');
Route::post('/kitchen/orders/{order}/bump', [KitchenController::class, 'bump'])->name('kitchen.bump');

// Touch POS Terminal System
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::post('/pos/open-session', [PosController::class, 'openSession'])->name('pos.open-session');
Route::post('/pos/close-session/{session}', [PosController::class, 'closeSession'])->name('pos.close-session');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

// Inventory Management
Route::resource('inventory', InventoryController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('/inventory/{inventory}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust-stock');

// Financial Analytics & Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
