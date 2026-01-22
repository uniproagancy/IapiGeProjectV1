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
    Route::post('/installment/{order_id}', '\App\Http\Controllers\ApiControllers\BogController@installment')->name('bog.installment');
    Route::post('/installment/callback', '\App\Http\Controllers\ApiControllers\BogController@installment')->name('bog.installment.callback');
    Route::post('/part-installment/{order_id}', '\App\Http\Controllers\ApiControllers\BogController@partInstallment')->name('bog.part-installment');
});