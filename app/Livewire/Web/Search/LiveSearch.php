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
        if (strlen($this->query) < 2) {
            return collect();
        }
        return Product::with(['translations', 'price'])
            ->where('show', 1)
            ->where(function ($query) {
                $query->where('id', 'like', '%' . $this->query . '%')
                    ->orWhere('sku', 'like', '%' . $this->query . '%')
                    ->orWhereHas('translations', function ($subQuery) {
                        $subQuery->where('title', 'like', "%{$this->query}%")
                            ->orWhere('description', 'like', "%{$this->query}%");
                    });
            })
            ->take(10)
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