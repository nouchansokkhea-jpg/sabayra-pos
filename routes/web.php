<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\PaymentWebhookController;
use App\Http\Controllers\Public\TelegramWebhookController;
use App\Models\Business;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Diagnostics endpoint
Route::get('/debug-db', function () {
    $results = [];
    try {
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
        $results['db_status'] = 'CONNECTED';
        $results['driver'] = \Illuminate\Support\Facades\DB::getDriverName();
        $results['database'] = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        $tables = \Illuminate\Support\Facades\DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
        $results['tables'] = array_map(fn($t) => $t->table_name ?? $t->TABLE_NAME, $tables);
    } catch (\Throwable $e) {
        $results['db_status'] = 'ERROR';
        $results['error'] = $e->getMessage();
    }
    return response()->json($results);
});

// Public SaaS Homepage
Route::get('/', function () {
    try {
        $plansEnabled = (bool) config('subscription.plans_enabled', false);
        $plans = $plansEnabled ? Plan::where('is_active', true)->orderBy('sort_order')->get() : collect();
    } catch (\Throwable $e) {
        $plans = collect();
    }

    try {
        $demoBusiness = Business::where('slug', 'romdoul-kitchen')->first();
    } catch (\Throwable $e) {
        $demoBusiness = null;
    }

    // Real Live System Metrics from Database
    try {
        $activeOutlets = Business::where('status', 'active')->count();
        $totalBranches = \App\Models\BusinessBranch::where('is_active', true)->count();
        $outletsCount = max($activeOutlets, $totalBranches);

        $totalQrScans = (int) \App\Models\QrCode::withoutGlobalScopes()->sum('scan_count');
        $totalScanEvents = (int) \App\Models\QrScanEvent::count();
        $totalMenuViews = max($totalQrScans, $totalScanEvents);

        $totalProducts = (int) \App\Models\Product::withoutGlobalScopes()->count();
        $totalOrders = (int) \App\Models\Order::withoutGlobalScopes()->count();
    } catch (\Throwable $e) {
        $outletsCount = 0;
        $totalMenuViews = 0;
        $totalProducts = 0;
        $totalOrders = 0;
    }

    $liveStats = [
        'active_outlets' => $outletsCount,
        'menu_views' => $totalMenuViews,
        'total_products' => $totalProducts,
        'total_orders' => $totalOrders,
    ];

    return view('welcome', compact('plans', 'demoBusiness', 'liveStats'));
})->name('home');

// Quick Locale Switcher
Route::get('/locale/{lang}', function (string $lang, Request $request) {
    if (in_array($lang, ['en', 'km'], true)) {
        session(['locale' => $lang]);
        App::setLocale($lang);

        if ($user = $request->user()) {
            $user->update(['preferred_locale' => $lang]);
        }
    }

    return back();
})->name('locale.switch');

// Unified Dashboard redirect
Route::get('/dashboard', function () {
    $user = Auth::user();
    if ($user && $user->is_super_admin) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('business.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Backward compatibility redirect for /business/* -> /app/*
Route::get('/business/{path}', function (Request $request, string $path) {
    $query = $request->getQueryString();
    return redirect('/app/' . $path . ($query ? '?' . $query : ''));
})->where('path', '.*');

// Telegram Official Bot Webhook
Route::post('/webhooks/telegram', [TelegramWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.telegram');

// ABA PayWay Official Webhook Callback
Route::post('/webhooks/aba-payway/{reference}', [PaymentWebhookController::class, 'abaPayWay'])
    ->middleware('throttle:120,1')
    ->name('webhooks.aba-payway');

require __DIR__.'/auth.php';
