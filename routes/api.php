<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('zoommer')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\ZoommerController@scan');
});

Route::prefix('alta')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\AltaController@scan');
});

Route::prefix('bog')->group(function () {
    Route::post('/payment/callback', '\App\Http\Controllers\ApiControllers\BOGPaymentController@callback')->name('bog.payment-callback');

    Route::post('/installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createInstallment')->name('bog.create-installment-order');

    Route::post('/part-installment/{order_id}', '\App\Http\Controllers\ApiControllers\BogController@partInstallment')->name('bog.part-installment');
});

Route::prefix('facebook')->group(function () {
    Route::get('/feed', '\App\Http\Controllers\ApiControllers\FacebookFeedController@getFeed')->name('facebook.get-feed');
});