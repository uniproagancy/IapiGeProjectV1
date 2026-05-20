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
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36',
                    'Accept'          => 'application/json, text/plain, */*',
                    'Accept-Language' => 'ka',
                    'Referer'         => 'https://alta.ge/',
                    'os'              => 'web',
                    'Cookie'          => 'alta-access_token=u87xozMQwBf8VXOvjJ63qCn4EZWRxmsqJot9BDncIHenc2fRVRMo0NpOIAlwTOyeQQT6QLKDUOp9GNQJICV8mYY57zOZCx%2BKeMkTRrQWxW2IR5MZGqhb%2FHBCQB%2F5vMVwTBVcJeZzc%2Fz3N2QY6E4D5rMs4a1YLtcvyiEtyYCmhuB5%2FiKMO8scKHjCW%2BoRDT1aYPPmiIQEbEN9ugtWYNSib0B843XJ3NYbUm2Gr1FYpFOnDnCgazsuZsadZurxIFI7fVzx5JXN1iU8ByNZ9skHbjO%2BN5thu660Zk4pIgToUBhgsUn5QLrTVwOAbysQYmMvNVY6InOrkMtgn3yEFxK%2F0BSi9uXWeRPG9IyzzfDeh6x1GC3b5h7RP4BMTc6F9vhjrHNp4hxOAgXsHbiyl8XycYY8TcyugnizUbv8P%2BRzwR5q%2FNdKn%2FIJ7zPTkskmswbSEDxPOSB36n41l4IFasWO7to%2FYG2jt2yyCQSE45OxiQBIC143bHy2Gh%2BYx8ZKVKX4GQQYRSswRwkWtgpzaEFv2YI3sDlv1TW3cSY5diczj1bdFh7j0IECdVTlkdYhtvv4EuYg5%2FBCGYr9S4RC3j%2FhG0UaDK3yXjEXHC3xOok2Seh%2BfcAcVft0VKS%2FNkagcSg4ZBY9rKnk6GW2ilsEpS8f4tRixNMHgzqDNkDOlShSvC13C3ClH6cxcqLpooIij38DhMyqdMBrDLdMOOLPshTRwH%2FyoGnLCc%2FS8O01lzUP9kgH9G9h5i9qsZ7aIcfDLxA8ekXPmzMSF23E90n4GHg23cElXAD9MjgXXMH9voGSVkhW%2Bie9HZU3SyiYdU%2BXPjMNbkKF8%2BKDFGMWGwV3mV8D%2B069vJEx2hZw2otcrO1mc%2F8hu32qi89n5I5EDhLQoStEdcM%2FnGbWmxuSz0Jo3nX9A3K0%2FZdvtduRcqS29UliALIfDfPTxL92JIBec2pRDSYRaS5KQ%2FL5jPXYhmKlZdNlzX%2Bo0dVje5QPLGWkf0CAD5Mz06Q7rLq7GbZ3HOQ2PuYbnDuJ; alta-is_user_session=0',
                ])
                ->get('https://alta.ge/api/proxy/v1/Products/details?productId=48069');

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
    Route::get('/missing-products', function () {
        $altaIds = \App\Models\AltaID::pluck('product_id')->toArray();

        $existingIds = \App\Models\Product\Product::where('sku', 'LIKE', 'ALTA-%')
            ->pluck('sku')
            ->map(fn($sku) => ltrim(str_replace('ALTA-', '', $sku), '0'))
            ->toArray();

        $missing = array_filter($altaIds, fn($id) => !in_array(ltrim((string) $id, '0'), $existingIds));

        return response()->json([
            'total_alta'   => count($altaIds),
            'total_exists' => count($existingIds),
            'missing'      => count($missing),
            'missing_ids'  => array_values($missing),
        ]);
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