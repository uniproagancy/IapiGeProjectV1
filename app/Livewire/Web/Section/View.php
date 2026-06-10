<?php

namespace App\Livewire\Web\Section;

use App\Models\Product\ProductSection;
use Livewire\Component;

class View extends Component
{
    public ProductSection $section;

    public function mount(string $slug): void
    {
        $this->section = ProductSection::where('slug', $slug)
            ->where('active', 1)
            ->firstOrFail();
    }

    public function render()
    {
        $products = $this->section->products()
            ->where('show', 1)
            ->where('active', 1)
            ->with(['translations', 'price', 'images'])
            ->get();

        return view('livewire.web.section.view', [
            'products' => $products,
        ])->layout('livewire.web.layout');
    }
}