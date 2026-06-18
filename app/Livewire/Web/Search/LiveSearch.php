<?php

namespace App\Livewire\Web\Search;

use Livewire\Component;
use App\Models\Product\Product;
use Livewire\Attributes\Computed;

class LiveSearch extends Component
{
    public $query = '';
    public $isOpen = false;
    public $selectedIndex = -1;

    public function updatedQuery()
    {
        $this->isOpen = strlen($this->query) >= 2;
        $this->selectedIndex = -1;
    }

    #[Computed]
    public function results()
    {
        $q = trim($this->query);

        if (mb_strlen($q) < 2) {
            return collect();
        }

        return Product::query()
            ->select('db_products.*')
            ->with([
                'translations' => fn($q) => $q->where('locale', app()->getLocale()),
                'price',
                'category.translations' => fn($q) => $q->where('locale', app()->getLocale()),
            ])
            ->leftJoin('db_product_categories', 'db_product_categories.id', '=', 'db_products.category_id')
            ->where('db_products.show', 1)
            ->where('db_products.active', 1)
            ->where(function ($query) use ($q) {
                $query->where('db_products.id', 'like', "%{$q}%")
                    ->orWhere('db_products.sku', 'like', "%{$q}%")
                    ->orWhereHas('translations', function ($sub) use ($q) {
                        $sub->where('title', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    });
            })
            // ✅ კატეგორიის sortable მიხედვით (პრიორიტეტი)
            ->orderByRaw('db_product_categories.sortable IS NULL ASC')
            ->orderBy('db_product_categories.sortable', 'ASC')
            // მარაგში მყოფი ჯერ
            ->orderByDesc('db_products.in_stock')
            // ბოლოს უახლესი
            ->orderByDesc('db_products.id')
            ->take(12)
            ->get();
    }

    public function selectProduct($slug)
    {
        $this->reset(['query', 'isOpen', 'selectedIndex']);
        return $this->redirect(route('web.products.view', $slug), navigate: true);
    }

    public function navigateDown()
    {
        if ($this->selectedIndex < $this->results->count() - 1) {
            $this->selectedIndex++;
        }
    }

    public function navigateUp()
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    public function selectCurrent()
    {
        if ($this->selectedIndex >= 0 && $this->selectedIndex < $this->results->count()) {
            $product = $this->results[$this->selectedIndex];
            $slug = $product->translation(app()->getLocale())->slug ?? $product->translation('ka')->slug;
            $this->selectProduct($slug);
        }
    }

    public function closeSearch()
    {
        $this->isOpen = false;
        $this->selectedIndex = -1;
    }

    public function render()
    {
        return view('livewire.web.search.live-search');
    }
}