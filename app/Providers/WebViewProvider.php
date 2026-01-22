<?php

namespace App\Providers;

use App\Models\Product\ProductCategory;
use App\Models\Content\WebMenu;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WebViewProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function($view) {
            $menu_list = WebMenu::where('active', 1)->get();
            $product_categories = ProductCategory::where([
                'active' => 1,
                'show' => 1,
                'parent_id' => 0,
            ])->orderBy('sortable', 'ASC')->get();
            $view->with('menu_list', $menu_list);
            $view->with('product_categories', $product_categories);
        });
    }
}
