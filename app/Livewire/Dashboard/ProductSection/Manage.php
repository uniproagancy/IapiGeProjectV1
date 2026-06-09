<?php

namespace App\Livewire\Dashboard\ProductSection;

use App\Models\Product\Product;
use App\Models\Product\ProductSection;
use Livewire\Component;

class Manage extends Component
{
    public ProductSection $section;
    public string $search = '';

    public function mount(int $id): void
    {
        $this->section = ProductSection::with('products')->findOrFail($id);
    }

    public function addProduct(int $productId): void
    {
        // უკვე არის?
        if ($this->section->products()->where('product_id', $productId)->exists()) {
            $this->dispatch('ui:error', message: 'პროდუქტი უკვე დამატებულია!');
            return;
        }

        $maxOrder = $this->section->products()->max('db_product_section_items.sort_order') ?? 0;
        $this->section->products()->attach($productId, ['sort_order' => $maxOrder + 1]);

        $this->section->load('products');
        $this->dispatch('ui:success', message: 'დაემატა!');
    }

    public function removeProduct(int $productId): void
    {
        $this->section->products()->detach($productId);
        $this->section->load('products');
        $this->dispatch('ui:success', message: 'მოშორდა!');
    }

    public function render()
    {
        $results = collect();
        if (mb_strlen(trim($this->search)) >= 2) {
            $addedIds = $this->section->products->pluck('id')->toArray();

            $results = Product::query()
                ->where(function ($q) {
                    $q->where('sku', 'like', "%{$this->search}%")
                        ->orWhereHas('translations', fn($t) =>
                        $t->where('locale', 'ka')->where('title', 'like', "%{$this->search}%")
                        );
                })
                ->whereNotIn('id', $addedIds)
                ->with('translations')
                ->limit(15)
                ->get();
        }

        return view('livewire.dashboard.product-section.manage', [
            'results' => $results,
        ])->layout('livewire.dashboard.layout');
    }
}