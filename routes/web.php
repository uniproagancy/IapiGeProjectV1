<?php

use App\Http\Controllers\InvoiceController;

use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;

use App\Models\Product\Product;
use App\Models\AltaID;

Route::get('/facebook-feed', '\App\Http\Controllers\FacebookFeedController@getFeed')->middleware('doNotCacheResponse')->name('facebook.get-feed');

Route::post('/cart/add', '\App\Http\Controllers\Web\CartController@add');
Route::post('/wishlist/toggle', '\App\Http\Controllers\Web\WishlistController@toggle');
Route::post('/wishlist/check', '\App\Http\Controllers\Web\WishlistController@check');

Route::group(['prefix' => LaravelLocalization::setLocale()], function () {

    Route::get('/debug-fbp', function () {
        return [
            'request_cookie'  => request()->cookie('_fbp'),
            'raw_cookie'      => $_COOKIE['_fbp'] ?? 'NOT FOUND',
            'all_cookies'     => array_keys(request()->cookies->all()),
        ];
    });

    Route::name('web.')->group(function () {
        Route::get('/update-alta', function (\Illuminate\Http\Request $request) {
            foreach(AltaID::all() as $alta) {
                Product::where('sku','ALTA-'.$alta->product_id)->update([
                    'show' => 1,
                ]);
            }
        })->middleware('doNotCacheResponse');

        Route::get('/', App\Livewire\Web\Main\Index::class)->name('main.index');

        Route::get('/contact', App\Livewire\Web\Main\Contact::class)->name('main.contact');
        Route::get('/about-us', App\Livewire\Web\Main\AboutUs::class)->name('main.about');

        Route::get('/checkout/success', '\App\Http\Controllers\OrderStatusController@success');
        Route::get('/checkout/reject', '\App\Http\Controllers\OrderStatusController@reject');
        Route::get('/checkout', App\Livewire\Web\Checkout\Checkout::class)->middleware('doNotCacheResponse')->name('checkout.index');

        Route::group(['middleware' => 'auth'], function () {
            Route::get('/user/{page?}', App\Livewire\Web\User\Index::class)->name('user.index');
        });

        Route::prefix('/products')->name('products.')->group(function () {
            Route::get('/{category_slug?}', App\Livewire\Web\Product\Index::class)->name('index');
            Route::get('/view/{slug?}', App\Livewire\Web\Product\View::class)->name('view');
        });

        Route::prefix('/section')->name('section.')->group(function () {
            Route::get('/{slug}', App\Livewire\Web\Section\View::class)->name('view');
        });

        Route::prefix('/promotions')->name('promotions.')->group(function () {
            Route::get('/{category_slug?}', App\Livewire\Web\Promotions\Index::class)->name('index');
        });

        Route::prefix('/static')->name('static.')->group(function () {
            Route::get('/{page?}', App\Livewire\Web\Main\StaticPages::class)->name('index');
        });

        Route::get('/logout', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return \Illuminate\Support\Facades\Redirect::route('web.main.index');
        })->middleware('doNotCacheResponse')->name('logout');

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

Route::prefix('/dashboard')->name('dashboard.')->middleware(DoNotCacheResponse::class)->group(function () {
    // AUTH ROUTES
    Route::middleware('guest')->group(function () {
        Route::get('/login', App\Livewire\Dashboard\Login::class)->name('login');
        Route::get('/forgot-password', App\Livewire\Dashboard\ForgotPassword::class)->name('password.forgot');
        Route::get('/reset-password/{hash}', App\Livewire\Dashboard\ResetPassword::class)->name('password.reset');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/global-export', function () {
            $rows = \App\Models\Product\MetroMartNotFound::orderBy('name')->get();
            $filename = 'not_found_' . now()->format('Y-m-d_His') . '.csv';

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, ['Name', 'Stock', 'Price', 'Reason', 'Date']);
                foreach ($rows as $r) {
                    fputcsv($out, [$r->name, $r->stock, $r->price, $r->reason, $r->created_at]);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        })->name('global.export')->middleware(['auth']);
        // MAIN
        Route::middleware(['check.role'])->group(function () {
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
                Route::get('/update/{id}', App\Livewire\Dashboard\Product\Update::class)->name('product.update');

                Route::get('/categories', App\Livewire\Dashboard\ProductCategory\Index::class)->name('product.category.index');
                Route::get('/brands', App\Livewire\Dashboard\ProductBrand\Index::class)->name('product.brand.index');

                Route::get('/sections', App\Livewire\Dashboard\ProductSection\Index::class)->name('sections.index');
                Route::get('/sections/{id}/manage', App\Livewire\Dashboard\ProductSection\Manage::class)->name('sections.manage');

                Route::get('/specifications', \App\Livewire\Dashboard\Specification\Index::class)
                    ->name('dashboard.specifications.index');

                // JSON იმპორტი — დააგდე ფაილი: storage/app/import/products_import.json
                // გახსენი: /dashboard/products/run-import?category=ID&supplier=ID
                Route::get('/run-import', function () {
                    $file = storage_path('app/import/products_import.json');

                    if (!file_exists($file)) {
                        return 'ფაილი ვერ მოიძებნა: ' . $file;
                    }

                    $products = json_decode(file_get_contents($file), true);

                    if (!$products) {
                        return 'JSON წაკითხვა ვერ მოხდა';
                    }

                    $categoryId = (int) request('category');
                    $supplierId = (int) request('supplier');
                    $brandId    = 143;

                    if (!$categoryId || !$supplierId) {
                        return 'მიუთითე: ?category=ID&supplier=ID';
                    }

                    \App\Jobs\ImportProductsJob::dispatch(
                        products: $products,
                        categoryId: $categoryId,
                        brandId: $brandId,
                        supplierId: $supplierId,
                    );

                    return '✅ იმპორტი დაიწყო — ' . count($products) . ' პროდუქტი. შეამოწმე logs.';
                })->name('product.run-import');
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
});

Route::post('/dashboard/product/image/upload', [
    \App\Http\Controllers\Dashboard\ProductImageUploadController::class, 'upload'
])->name('dashboard.product.image.upload')->middleware('auth');