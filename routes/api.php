<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('zoommer')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\ZoommerController@scan');
});

Route::prefix('alta')->group(function () {
    Route::get('/getProducts', '\App\Http\Controllers\ApiControllers\AltaController@getProducts');
    Route::get('/test-connection', function () {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get(
                'https://alta.ge/api/proxy/v1/Products/details?productId=48060'
            );

            return response()->json([
                'status'  => $response->status(),
                'success' => $response->successful(),
                'body'    => $response->json(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        }
    });
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
    Route::get('/installment/callback', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@installmentCheck')->name('bog.installment-callback');
    Route::post('/installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createInstallment')->name('bog.create-installment-order');
    Route::post('/part-installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createPartInstallment')->name('bog.create-part-installment-order');
});

Route::prefix('tbc')->group(function () {
    Route::get('/installment/status', '\App\Http\Controllers\ApiControllers\TBCInstallmentController@status')->name('tbc.installment-status');
});

Route::prefix('credo')->group(function () {
    Route::get('/create/order', '\App\Http\Controllers\ApiControllers\CredoController@createOrder')->name('credo-create-order');
});

Route::prefix('ushop')->group(function () {
    Route::get('/import', '\App\Http\Controllers\ApiControllers\UshopController@import')->name('ushop-index');
});