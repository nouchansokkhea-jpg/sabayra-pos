<?php

use App\Http\Controllers\Public\OrderCheckoutController;
use App\Http\Controllers\Public\PublicMenuController;
use Illuminate\Support\Facades\Route;

Route::name('public.')->group(function () {
    Route::get('/{business_slug}/table/{table_number}', [PublicMenuController::class, 'table'])
        ->where(['business_slug' => '[a-zA-Z0-9\-]+', 'table_number' => '[a-zA-Z0-9\-]+'])
        ->name('table');

    Route::post('/{business_slug}/checkout', [OrderCheckoutController::class, 'checkout'])
        ->where('business_slug', '[a-zA-Z0-9\-]+')
        ->name('checkout');

    Route::get('/{business_slug}/order/{order_number}', [PublicMenuController::class, 'orderStatus'])
        ->where(['business_slug' => '[a-zA-Z0-9\-]+', 'order_number' => '[a-zA-Z0-9\-]+'])
        ->name('order-status');

    Route::get('/{business_slug}', [PublicMenuController::class, 'show'])
        ->where('business_slug', '[a-zA-Z0-9\-]+')
        ->name('menu');
});
