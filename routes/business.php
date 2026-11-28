<?php

use App\Http\Controllers\Business\AiAssistantController;
use App\Http\Controllers\Business\AiCenterController;
use App\Http\Controllers\Business\AiInsightController;
use App\Http\Controllers\Business\AiInventoryForecastController;
use App\Http\Controllers\Business\AiMarketingController;
use App\Http\Controllers\Business\AiMenuBuilderController;
use App\Http\Controllers\Business\AiMenuCreatorController;
use App\Http\Controllers\Business\AiSalesPredictionController;
use App\Http\Controllers\Business\AnalyticsController;
use App\Http\Controllers\Business\ArAssistantController;
use App\Http\Controllers\Business\BranchController;
use App\Http\Controllers\Business\BusinessWorkspaceController;
use App\Http\Controllers\Business\CategoryController;
use App\Http\Controllers\Business\CouponController;
use App\Http\Controllers\Business\CustomerController;
use App\Http\Controllers\Business\DashboardController;
use App\Http\Controllers\Business\DeliveryZoneController;
use App\Http\Controllers\Business\GuideController;
use App\Http\Controllers\Business\HappyHourController;
use App\Http\Controllers\Business\HelpCenterController;
use App\Http\Controllers\Business\InventoryController;
use App\Http\Controllers\Business\KitchenController;
use App\Http\Controllers\Business\LiveOrderController;
use App\Http\Controllers\Business\LoyaltyController;
use App\Http\Controllers\Business\NotificationPreferencesController;
use App\Http\Controllers\Business\OrderManagementController;
use App\Http\Controllers\Business\PaymentSettingsController;
use App\Http\Controllers\Business\PosController;
use App\Http\Controllers\Business\PrinterController;
use App\Http\Controllers\Business\ProductController;
use App\Http\Controllers\Business\ProductImportController;
use App\Http\Controllers\Business\QrStudioController;
use App\Http\Controllers\Business\RecipeController;
use App\Http\Controllers\Business\ReferralController;
use App\Http\Controllers\Business\ReportController;
use App\Http\Controllers\Business\ReservationController;
use App\Http\Controllers\Business\ReviewController;
use App\Http\Controllers\Business\SecurityCenterController;
use App\Http\Controllers\Business\SessionSecurityController;
use App\Http\Controllers\Business\SettingController;
use App\Http\Controllers\Business\StaffController;
use App\Http\Controllers\Business\StorefrontTemplateController;
use App\Http\Controllers\Business\TwoFactorController;
use App\Http\Controllers\Business\SubscriptionController;
use App\Http\Controllers\Business\SupplierController;
use App\Http\Controllers\Business\TableController;
use App\Http\Controllers\Business\TelegramSettingsController;
use App\Http\Controllers\Business\TranslationController;
use App\Http\Controllers\Business\WaitlistController;
use Illuminate\Support\Facades\Route;

// Workspace onboarding & switching
Route::get('/create-business', [BusinessWorkspaceController::class, 'create'])->name('create');
Route::post('/create-business', [BusinessWorkspaceController::class, 'store'])->name('store');
Route::post('/switch-business/{business}', [BusinessWorkspaceController::class, 'switch'])->name('switch');

// ==========================================
// 1. OVERVIEW
// ==========================================
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/refresh-ai', [DashboardController::class, 'refreshAiInsights'])->name('dashboard.refresh-ai');

// ==========================================
// 2. CATALOG & MENU
// ==========================================
// Products Bulk Import (must precede products resource to avoid slug collision)
Route::get('/products/import/template', [ProductImportController::class, 'downloadTemplate'])->name('products.import.template');
Route::get('/products/import', [ProductImportController::class, 'index'])->name('products.import');
Route::post('/products/import', [ProductImportController::class, 'store'])->name('products.import.store');

Route::resource('products', ProductController::class);
Route::post('/products/{product}/toggle-availability', [ProductController::class, 'toggleAvailability'])->name('products.toggle-availability');

Route::resource('categories', CategoryController::class);
Route::post('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

Route::get('/storefront-templates', [StorefrontTemplateController::class, 'index'])->name('storefront.templates');
Route::put('/storefront-templates', [StorefrontTemplateController::class, 'update'])->name('storefront.templates.update');

Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
Route::put('/translations', [TranslationController::class, 'update'])->name('translations.update');

Route::get('/ai-menu-builder', [AiMenuBuilderController::class, 'index'])->name('ai.menu-builder');
Route::post('/ai-menu-builder', [AiMenuBuilderController::class, 'store'])->name('ai.menu-builder.store');

// ==========================================
// 3. OPERATIONS
// ==========================================
// Live Orders Kanban & Polling
Route::get('/orders/live', [LiveOrderController::class, 'index'])->name('orders.live');
Route::get('/orders/live/api', [LiveOrderController::class, 'pollApi'])->name('orders.live.api');
Route::get('/orders/live/poll', [LiveOrderController::class, 'pollApi'])->name('orders.live.poll');

// Standard Orders
Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
Route::get('/orders/{order}', [OrderManagementController::class, 'show'])->name('orders.show');
Route::post('/orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.update-status');
Route::post('/orders/{order}/payment', [OrderManagementController::class, 'updatePayment'])->name('orders.update-payment');
Route::post('/orders/{order}/payment/approve', [OrderManagementController::class, 'approvePayment'])->name('orders.payment.approve');
Route::post('/orders/{order}/payment/reject', [OrderManagementController::class, 'rejectPayment'])->name('orders.payment.reject');
Route::post('/orders/{order}/payment/collect-cod', [OrderManagementController::class, 'collectCod'])->name('orders.payment.collect-cod');
Route::get('/orders/{order}/payment-proof/{proof}', [OrderManagementController::class, 'showProof'])->name('orders.payment-proof.show');
Route::get('/orders/{order}/receipt', [OrderManagementController::class, 'receipt'])->name('orders.receipt');
Route::get('/orders/{order}/kitchen-ticket', [OrderManagementController::class, 'kitchenTicket'])->name('orders.kitchen-ticket');

// Reservations
Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
Route::post('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('reservations.update-status');

// Waitlist
Route::get('/waitlist', [WaitlistController::class, 'index'])->name('waitlist.index');
Route::post('/waitlist', [WaitlistController::class, 'store'])->name('waitlist.store');
Route::post('/waitlist/{waitlist}/status', [WaitlistController::class, 'updateStatus'])->name('waitlist.update-status');

// Tables & QR Codes
Route::get('/tables/master/download-png', [TableController::class, 'downloadMasterQrPng'])->name('tables.master.download-png');
Route::get('/tables/master/standee', [TableController::class, 'masterStandee'])->name('tables.master.standee');
Route::get('/tables/{table}/download-png', [TableController::class, 'downloadTableQrPng'])->name('tables.download-png');
Route::get('/tables/{table}/standee', [TableController::class, 'tableStandee'])->name('tables.standee');
Route::resource('tables', TableController::class)->only(['index', 'store', 'update', 'destroy']);

// Kitchen Display System (KDS)
Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
Route::get('/kitchen/api/orders', [KitchenController::class, 'ordersApi'])->name('kitchen.api');
Route::post('/kitchen/orders/{order}/bump', [KitchenController::class, 'bump'])->name('kitchen.bump');

// Touch POS
Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
Route::post('/pos/open-session', [PosController::class, 'openSession'])->name('pos.open-session');
Route::post('/pos/close-session/{session}', [PosController::class, 'closeSession'])->name('pos.close-session');
Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

// Printers
Route::get('/printers', [PrinterController::class, 'index'])->name('printers.index');
Route::post('/printers', [PrinterController::class, 'store'])->name('printers.store');
Route::put('/printers/{printer}', [PrinterController::class, 'update'])->name('printers.update');
Route::delete('/printers/{printer}', [PrinterController::class, 'destroy'])->name('printers.destroy');
Route::get('/printers/{printer}/test-print', [PrinterController::class, 'testPrint'])->name('printers.test-print');

// ==========================================
// 4. MANAGEMENT
// ==========================================
Route::post('/staff/transfer-ownership', [StaffController::class, 'transferOwnership'])->name('staff.transfer-ownership');
Route::resource('staff', StaffController::class)->only(['index', 'store', 'update', 'destroy']);

Route::resource('inventory', InventoryController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('/inventory/{inventory}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust-stock');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');

Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::post('/suppliers/po', [SupplierController::class, 'storePo'])->name('suppliers.po.store');
Route::post('/suppliers/po/{po}/status', [SupplierController::class, 'updatePoStatus'])->name('suppliers.po.status');

Route::resource('branches', BranchController::class)->only(['index', 'store', 'update', 'destroy']);

Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery-zones.index');
Route::post('/delivery-zones', [DeliveryZoneController::class, 'store'])->name('delivery-zones.store');
Route::delete('/delivery-zones/{zone}', [DeliveryZoneController::class, 'destroy'])->name('delivery-zones.destroy');

// ==========================================
// 5. CUSTOMER & MARKETING
// ==========================================
Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('/reviews/{review}/respond', [ReviewController::class, 'respond'])->name('reviews.respond');
Route::post('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])->name('reviews.update-status');

Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
Route::post('/coupons/{coupon}/toggle', [CouponController::class, 'toggleStatus'])->name('coupons.toggle');
Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');

Route::get('/happy-hours', [HappyHourController::class, 'index'])->name('happy-hours.index');
Route::post('/happy-hours', [HappyHourController::class, 'store'])->name('happy-hours.store');
Route::post('/happy-hours/{happyHour}/toggle', [HappyHourController::class, 'toggle'])->name('happy-hours.toggle');
Route::delete('/happy-hours/{happyHour}', [HappyHourController::class, 'destroy'])->name('happy-hours.destroy');

Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
Route::put('/loyalty', [LoyaltyController::class, 'update'])->name('loyalty.update');

Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
Route::post('/referrals', [ReferralController::class, 'store'])->name('referrals.store');
Route::delete('/referrals/{referral}', [ReferralController::class, 'destroy'])->name('referrals.destroy');

// ==========================================
// 6. BUSINESS INTELLIGENCE
// ==========================================
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

// Unified SABAYRA AI Center
Route::prefix('ai')->name('ai.')->group(function (): void {
    Route::get('/', [AiCenterController::class, 'overview'])->name('overview');
    Route::get('/assistant', [AiAssistantController::class, 'index'])->name('assistant');
    Route::post('/assistant/ask', [AiAssistantController::class, 'ask'])->name('assistant.ask');
    Route::delete('/assistant/conversations/{conversation}', [AiAssistantController::class, 'destroy'])->name('assistant.destroy');
    Route::get('/menu-creator', [AiMenuCreatorController::class, 'index'])->name('menu-creator');
    Route::post('/menu-creator/generate', [AiMenuCreatorController::class, 'generate'])->name('menu-creator.generate');
    Route::post('/menu-creator/{content}/apply', [AiMenuCreatorController::class, 'apply'])->name('menu-creator.apply');
    Route::get('/marketing-studio', [AiMarketingController::class, 'index'])->name('marketing');
    Route::post('/marketing-studio/generate', [AiMarketingController::class, 'generate'])->name('marketing.generate');
    Route::delete('/marketing-studio/{content}', [AiMarketingController::class, 'destroy'])->name('marketing.destroy');
    Route::get('/inventory-forecast', [AiInventoryForecastController::class, 'index'])->name('inventory-forecast');
    Route::post('/inventory-forecast/run', [AiInventoryForecastController::class, 'run'])->name('inventory-forecast.run');
    Route::get('/sales-prediction', [AiSalesPredictionController::class, 'index'])->name('sales-prediction');
    Route::post('/sales-prediction/run', [AiSalesPredictionController::class, 'run'])->name('sales-prediction.run');
    Route::get('/usage', [AiCenterController::class, 'usage'])->name('usage');
    Route::get('/settings', [AiCenterController::class, 'settings'])->name('settings');
    Route::put('/settings', [AiCenterController::class, 'updateSettings'])->name('settings.update');

    // Actionable AI Insights & Legacy Algorithmic BI Modules
    Route::get('/insights', [AiInsightController::class, 'weeklySummary'])->name('insights');
    Route::post('/insights/generate', [AiInsightController::class, 'generate'])->name('insights.generate');
    Route::patch('/insights/{insight}/status', [AiInsightController::class, 'updateStatus'])->name('insights.status');
    Route::get('/menu-audit', [AiInsightController::class, 'menuAudit'])->name('menu-audit');
    Route::get('/sentiment', [AiInsightController::class, 'sentimentRadar'])->name('sentiment');
    Route::get('/upsell-pairings', [AiInsightController::class, 'upsellPairings'])->name('upsell-pairings');
});

// ==========================================
// 7. QR & ADVANCED FEATURES
// ==========================================
Route::get('/qr-codes', [QrStudioController::class, 'index'])->name('qr.index');
Route::get('/qr-studio', [QrStudioController::class, 'index'])->name('qr-studio.index');
Route::put('/qr-codes/settings', [QrStudioController::class, 'updateSettings'])->name('qr.update-settings');

Route::get('/ar-cards', [ArAssistantController::class, 'arCards'])->name('ar.cards');
Route::get('/3d-models', [ArAssistantController::class, 'scanAssistant'])->name('ar.scan-assistant');
Route::post('/3d-models', [ArAssistantController::class, 'storeAsset'])->name('ar.store-asset');

// ==========================================
// 8. LEARNING & SUPPORT
// ==========================================
Route::get('/workflow-guide', [GuideController::class, 'orderFlow'])->name('workflow-guide');
Route::get('/guides/order-flow', [GuideController::class, 'orderFlow'])->name('guides.order-flow');
Route::get('/tutorials', [GuideController::class, 'tutorials'])->name('tutorials.index');
Route::get('/guides/tutorials', [GuideController::class, 'tutorials'])->name('guides.tutorials');

Route::get('/help', [HelpCenterController::class, 'index'])->name('help.index');
Route::post('/help/tickets', [HelpCenterController::class, 'storeTicket'])->name('help.store-ticket');

// ==========================================
// 9. ACCOUNT
// ==========================================
Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');

Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
Route::put('/settings/general', [SettingController::class, 'updateGeneral'])->name('settings.general.update');
Route::put('/settings/business', [SettingController::class, 'updateBusinessInfo'])->name('settings.business.update');
Route::put('/settings/experience', [SettingController::class, 'updateCategoryExperience'])->name('settings.experience.update');
Route::put('/settings/payment', [PaymentSettingsController::class, 'update'])->name('settings.payment.update');
Route::post('/settings/payment/verify-bakong', [PaymentSettingsController::class, 'verifyBakong'])->name('settings.payment.verify-bakong');
Route::post('/settings/payment/test-aba', [PaymentSettingsController::class, 'testAbaConnection'])->name('settings.payment.test-aba');
Route::put('/settings/account', [SettingController::class, 'updateAccount'])->name('settings.account.update');
Route::put('/settings/account/password', [SettingController::class, 'updatePassword'])->name('settings.account.password');

Route::post('/settings/telegram/connect', [TelegramSettingsController::class, 'connect'])->name('settings.telegram.connect');
Route::post('/settings/telegram/custom', [TelegramSettingsController::class, 'saveCustomBot'])->name('settings.telegram.custom');
Route::patch('/settings/telegram/toggle', [TelegramSettingsController::class, 'toggle'])->name('settings.telegram.toggle');
Route::post('/settings/telegram/test', [TelegramSettingsController::class, 'test'])->name('settings.telegram.test');
Route::post('/settings/telegram/disconnect', [TelegramSettingsController::class, 'disconnect'])->name('settings.telegram.disconnect');
Route::get('/settings/telegram/status', [TelegramSettingsController::class, 'status'])->name('settings.telegram.status');

Route::put('/settings/notifications', [NotificationPreferencesController::class, 'update'])->name('settings.notifications.update');
Route::post('/notifications/{notificationId}/read', [NotificationPreferencesController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationPreferencesController::class, 'markAllRead'])->name('notifications.read-all');

Route::get('/security', [SecurityCenterController::class, 'index'])->name('security.index');
Route::post('/security/2fa', [SecurityCenterController::class, 'toggleTwoFactor'])->name('security.2fa');
Route::post('/security/2fa/setup', [TwoFactorController::class, 'setup'])->name('security.2fa.setup');
Route::post('/security/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('security.2fa.confirm');
Route::post('/security/2fa/cancel', [TwoFactorController::class, 'cancel'])->name('security.2fa.cancel');
Route::post('/security/2fa/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('security.2fa.recovery-codes');
Route::delete('/security/2fa', [TwoFactorController::class, 'disable'])->name('security.2fa.disable');
Route::delete('/security/sessions/{sessionRef}', [SessionSecurityController::class, 'destroy'])->name('security.sessions.destroy');
Route::post('/security/sessions/revoke-others', [SessionSecurityController::class, 'destroyOthers'])->name('security.sessions.revoke-others');
Route::put('/settings/security', [SessionSecurityController::class, 'updateSecuritySettings'])->name('settings.security.update');
