<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::controller(PaymentController::class)->group(function () {
    Route::prefix('payment')->group(function () {
        Route::get('/', 'index')->name('payment');
        Route::get('show_payment_gateway_by_ajax/{identifier}', 'show_payment_gateway_by_ajax')->name('payment.show_payment_gateway_by_ajax');
        Route::get('success/{identifier}', 'payment_success')->name('payment.success');
        Route::get('create/{identifier}', 'payment_create')->name('payment.create');

        Route::post('{identifier}/order', 'payment_razorpay')->name('razorpay.order');

        Route::post('make/order/{identifier}', 'payment_paytm')->name('make.order');
        Route::get('make/{identifier}/status', 'paytm_paymentCallback')->middleware('throttle:webhook')->name('payment.status');
    });

    Route::post('paystack/payment/{identifier}', 'payment_success')->middleware('throttle:webhook')->name('make.payment');
});
