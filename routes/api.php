<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('zoommer')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\ZoommerController@scan');
});

Route::prefix('alta')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\AltaController@scan');
    Route::get('/trash', '\App\Http\Controllers\ApiControllers\AltaController@altaTrash');
    Route::get('/update', '\App\Http\Controllers\ApiControllers\AltaController@updateActive');
});

Route::prefix('citrus')->group(function () {
    Route::get('/get', '\App\Http\Controllers\ApiControllers\CitrusController@get');
});

Route::prefix('promotions')->group(function () {
    Route::get('/get', '\App\Http\Controllers\ApiControllers\PromotionProductsController@index');
});


Route::prefix('comfo')->group(function () {
    Route::get('/get', '\App\Http\Controllers\ApiControllers\DataController@importData');
});

Route::prefix('json')->group(function () {
    Route::get('/loadJson', '\App\Http\Controllers\ApiControllers\JsonParseController@loadJson');
});

Route::prefix('bog')->group(function () {
    Route::post('/payment/callback', '\App\Http\Controllers\ApiControllers\BOGPaymentController@callback')->name('bog.payment-callback');
    Route::post('/installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createInstallment')->name('bog.create-installment-order');
    Route::post('/part-installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createPartInstallment')->name('bog.create-part-installment-order');
});

Route::prefix('credo')->group(function () {
    Route::get('/create/order', '\App\Http\Controllers\ApiControllers\CredoController@createOrder')->name('credo-create-order');
});