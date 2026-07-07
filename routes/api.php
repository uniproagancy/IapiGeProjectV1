<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('zoommer')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\ZoommerController@scan');
    Route::get('/debug', '\App\Http\Controllers\ApiControllers\ZoommerController@debug');
});

Route::prefix('elite')->group(function () {
    Route::get('/scan', '\App\Http\Controllers\ApiControllers\EliteController@scan');
});

Route::prefix('alta')->group(function () {
    Route::get('/check', [\App\Http\Controllers\ApiControllers\AltaController::class, 'check']);
    Route::get('/scan', [\App\Http\Controllers\ApiControllers\AltaController::class, 'scan']);
    Route::get('/getProducts', '\App\Http\Controllers\ApiControllers\AltaController@getProducts');
    Route::get('/debug', [\App\Http\Controllers\ApiControllers\AltaController::class, 'debug']);

});

Route::prefix('citrus')->group(function () {
    Route::get('/import', '\App\Http\Controllers\ApiControllers\CitrusController@import');
});

Route::prefix('upload')->group(function () {
    Route::post('/image', [\App\Http\Controllers\ApiControllers\ImageUploadController::class, 'upload']);
});

Route::prefix('promotions')->group(function () {
    Route::get('/get', '\App\Http\Controllers\ApiControllers\PromotionProductsController@index');
});

Route::prefix('comfo')->group(function () {
    Route::get('/get', '\App\Http\Controllers\ApiControllers\DataController@importData');
});

Route::prefix('bog')->group(function () {
    Route::post('/payment/callback', '\App\Http\Controllers\ApiControllers\BOGPaymentController@callback')->name('bog.payment-callback');
    Route::get('/installment/callback', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@installmentCheck')->name('bog.installment-callback');
    Route::post('/installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createInstallment')->name('bog.create-installment-order');
    Route::post('/part-installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createPartInstallment')->name('bog.create-part-installment-order');
    Route::post('/part-installment/{order_id}', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@createPartInstallment')->name('bog.create-part-installment-order');
    Route::get('/refund', '\App\Http\Controllers\ApiControllers\BogRefundController@refund')->name('bog.refund');

});

Route::get('/allmarket/scan', [\App\Http\Controllers\ApiControllers\AllmarketController::class, 'scan']);

Route::prefix('tbc')->group(function () {
    Route::get('/installment/status', '\App\Http\Controllers\ApiControllers\TBCInstallmentController@status')->name('tbc.installment-status');
    Route::get('/installment/one/status', '\App\Http\Controllers\ApiControllers\TBCInstallmentController@checkOne')->name('tbc.installment-status-one');
});

Route::prefix('alneo')->group(function () {
    Route::get('/scan', [\App\Http\Controllers\ApiControllers\AlneoController::class, 'scan']);
});

Route::prefix('credo')->group(function () {
    Route::get('/create/order', '\App\Http\Controllers\ApiControllers\CredoController@createOrder')->name('credo-create-order');
});

Route::prefix('thermocenter')->group(function () {
    Route::get('/scan', [\App\Http\Controllers\ApiControllers\ThermocenterController::class, 'scan']);
});

Route::get('/generate-feed-now', function() {
    app(\App\Http\Controllers\FacebookFeedController::class)->regenerate();
    return 'done';
});