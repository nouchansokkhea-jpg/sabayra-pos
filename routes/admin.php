<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SupportController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// 1. Dashboard Overview
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// 2. Businesses Management (All Businesses, Details, Suspend/Activate, Delete)
Route::get('/businesses', [BusinessController::class, 'index'])->name('businesses.index');
Route::get('/businesses/{business}', [BusinessController::class, 'show'])->name('businesses.show');
Route::post('/businesses/{business}/toggle-status', [BusinessController::class, 'toggleStatus'])->name('businesses.toggle-status');
Route::delete('/businesses/{business}', [BusinessController::class, 'destroy'])->name('businesses.destroy');

// 3. User Management
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

// 4. Subscriptions Management
Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
Route::post('/subscriptions/{subscription}/update-status', [SubscriptionController::class, 'updateStatus'])->name('subscriptions.update-status');

// 5. Pricing Plans Management
Route::resource('plans', PlanController::class);

// 6. Payments Management (SaaS Subscription Payments & Transactions)
Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
Route::post('/payments/{payment}/update-status', [PaymentController::class, 'updateStatus'])->name('payments.update-status');

// 7. Platform Categories & Sector Presets
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

// 8. Revenue & Platform Analytics
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

// 9. Support Management (Helpdesk & Inquiries)
Route::get('/support', [SupportController::class, 'index'])->name('support.index');
Route::post('/support/{ticket}/update-status', [SupportController::class, 'updateStatus'])->name('support.update-status');

// 10. System Activity Logs
Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

// 11. Platform Settings
Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
Route::post('/settings/ai', [SettingController::class, 'updateAi'])->name('settings.ai.update');
Route::post('/settings/ai/test', [SettingController::class, 'testAi'])->name('settings.ai.test');
