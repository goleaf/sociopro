<?php

use App\Http\Controllers\PaymentHistory;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')
    ->name('user.')
    ->middleware('auth', 'user', 'verified', 'activity', 'prevent-back-history')
    ->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::get('dashboard', 'dashboard')->name('dashboard');

            Route::get('ads', 'ads')->name('ads');

            Route::prefix('ad')->name('ad.')->group(function () {
                Route::get('create', 'ad_create')->name('create');
                Route::post('store', 'ad_store')->name('store');
                Route::get('edit/{id}', 'ad_edit')->name('edit');
                Route::post('update/{id}', 'ad_update')->name('update');
                Route::get('delete/{id}', 'ad_delete')->name('delete');
                Route::get('ad_charge_by_daterange', 'ad_charge_by_daterange')->name('ad_charge_by_daterange');
                Route::post('payment_configuration/{id}', 'payment_configuration')->name('payment_configuration');
                Route::get('payment_success/{identifier}', 'payment_success')->name('payment_success');
            });
        });

        Route::controller(PaymentHistory::class)->group(function () {
            Route::get('payment-histories', 'index')->name('payment_histories');
        });
    });
