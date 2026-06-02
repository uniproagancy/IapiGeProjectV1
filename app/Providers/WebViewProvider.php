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
        View::composer('*', function ($view) {
            $menu_list = cache()->remember('web_menu_list', 3600, fn () =>
            WebMenu::where('active', 1)->get()
            );

            $product_categories = cache()->remember('web_product_categories_nav', 3600, fn () =>
            ProductCategory::where([
                'active'    => 1,
                'show'      => 1,
                'parent_id' => 0,
            ])
                ->where(function ($q) {
                    $q->whereHas('products', fn ($p) => $p->where('show', 1)->where('active', 1))
                        ->orWhereHas('children.products', fn ($p) => $p->where('show', 1)->where('active', 1));
                })
                ->with(['children' => fn ($c) => $c
                    ->where('active', 1)
                    ->where('show', 1)
                    ->whereHas('products', fn ($p) => $p->where('show', 1)->where('active', 1))
                ])
                ->orderBy('sortable', 'ASC')
                ->get()
            );

            $view->with('menu_list', $menu_list);
            $view->with('product_categories', $product_categories);
        });
    }
}
