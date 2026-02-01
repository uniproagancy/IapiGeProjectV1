<?php

use App\Http\Controllers\ApiControllers\SocialLoginController;
use App\Http\Controllers\InvoiceController;

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => LaravelLocalization::setLocale()], function () {
    Route::name('web.')->group(function () {
        Route::get('/', App\Livewire\Web\Main\Index::class)->name('main.index');
        Route::get('/contact', App\Livewire\Web\Main\Contact::class)->name('main.contact');
        Route::get('/about-us', App\Livewire\Web\Main\AboutUs::class)->name('main.contact');

        Route::group(['middleware' => 'auth'], function () {
            Route::get('/user/{page?}', App\Livewire\Web\User\Index::class)->name('user.index');
            Route::get('/checkout', App\Livewire\Web\Checkout\Checkout::class)->name('checkout.index');
        });

        Route::prefix('/products')->name('products.')->group(function () {
            Route::get('/{category_slug?}', App\Livewire\Web\Product\Index::class)->name('index');
            Route::get('/view/{slug?}', App\Livewire\Web\Product\View::class)->name('view');
        });

        Route::prefix('/static')->name('static.')->group(function () {
            Route::get('/{page?}', App\Livewire\Web\Main\StaticPages::class)->name('index');
        });

        Route::get('/logout', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return \Illuminate\Support\Facades\Redirect::route('web.main.index');
        })->name('logout');

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle);
        });
    });

    Route::get('/bog/installment/redirect', '\App\Http\Controllers\ApiControllers\BOGInstallmentController@installmentRedirect')->name('bog.installment-redirect');

//    Route::prefix('auth')->name('auth.')->group(function () {
    // Google
//        Route::get('google', [SocialLoginController::class, 'redirectToGoogle'])->name('google');
//        Route::get('google/callback', [SocialLoginController::class, 'handleGoogleCallback'])->name('google.callback');
//
//        // Facebook
//        Route::get('facebook', [SocialLoginController::class, 'redirectToFacebook'])->name('facebook');
//        Route::get('facebook/callback', [SocialLoginController::class, 'handleFacebookCallback'])->name('facebook.callback');
//    });
});

Route::prefix('/dashboard')->name('dashboard.')->group(function () {
    // AUTH ROUTES
    Route::middleware('guest')->group(function () {
        Route::get('/login', App\Livewire\Dashboard\Login::class)->name('login');
        Route::get('/forgot-password', App\Livewire\Dashboard\ForgotPassword::class)->name('password.forgot');
        Route::get('/reset-password/{hash}', App\Livewire\Dashboard\ResetPassword::class)->name('password.reset');
    });

    Route::middleware('auth')->group(function () {
        // MAIN
        Route::get('/', App\Livewire\Dashboard\Main\Index::class)->name('main');

        //USERS
        Route::prefix('users')->group(function () {
            Route::get('/', App\Livewire\Dashboard\User\Index::class)->name('user.index');
            Route::get('/view/{user_id}/{page?}', App\Livewire\Dashboard\User\View::class)->name('user.view');
        });

        //COMPANIES
        Route::prefix('companies')->group(function () {
            Route::get('/', App\Livewire\Dashboard\Company\Index::class)->name('company.index');
        });

        //PRODUCTS
        Route::prefix('products')->group(function () {
            Route::get('/', App\Livewire\Dashboard\Product\Index::class)->name('product.index');
            Route::get('/create', App\Livewire\Dashboard\Product\Create::class)->name('product.create');
            Route::get('/update/{product_id}', App\Livewire\Dashboard\Product\Update::class)->name('product.update');

            Route::get('/categories', App\Livewire\Dashboard\ProductCategory\Index::class)->name('product.category.index');
            Route::get('/brands', App\Livewire\Dashboard\ProductBrand\Index::class)->name('product.brand.index');
        });

        //ORDERS
        Route::prefix('orders')->group(function () {
            Route::get('/', App\Livewire\Dashboard\Order\Index::class)->name('order.index');
            Route::get('/view/{order_id}', App\Livewire\Dashboard\Order\View::class)->name('order.view');
            Route::get('/view/{order_id}/invoice/download', [InvoiceController::class, 'invoiceDownload'])
                ->name('order.invoice.download');
            Route::get('/view/{order_id}/invoice/send', [InvoiceController::class, 'invoiceSend'])
                ->name('order.invoice.send');
        });

        Route::get('/logout', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return \Illuminate\Support\Facades\Redirect::route('dashboard.login');
        })->name('logout');

    });
});