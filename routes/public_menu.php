<?php

use App\Http\Controllers\Public\CustomerOrderPaymentController;
use App\Http\Controllers\Public\OrderCheckoutController;
use App\Http\Controllers\Public\PublicBookingController;
use App\Http\Controllers\Public\PublicMenuController;
use Illuminate\Support\Facades\Route;

Route::name('public.')->group(function () {
    Route::get('/{business_slug}/booking-slots', [PublicBookingController::class, 'slots'])
        ->name('booking-slots');

    Route::post('/{business_slug}/book', [PublicBookingController::class, 'store'])
        ->name('book');

    Route::get('/{business_slug}/booking/{booking_code}', [PublicBookingController::class, 'show'])
        ->name('booking-status');

    Route::get('/{business_slug}/table/{table_number}', [PublicMenuController::class, 'table'])
        ->name('table');

    Route::post('/{business_slug}/checkout', [OrderCheckoutController::class, 'checkout'])
        ->name('checkout');

    Route::get('/{business_slug}/order/{order_number}', [PublicMenuController::class, 'orderStatus'])
        ->name('order-status');

    Route::post('/{business_slug}/order/{order_number}/proof', [CustomerOrderPaymentController::class, 'uploadProof'])
        ->middleware('throttle:20,1')
        ->name('order-proof.upload');

    Route::post('/{business_slug}/order/{order_number}/verify-payment', [CustomerOrderPaymentController::class, 'verifyStatus'])
        ->middleware('throttle:60,1')
        ->name('order-payment.verify');

    Route::get('/{business_slug}', [PublicMenuController::class, 'show'])
        ->name('menu');
});
