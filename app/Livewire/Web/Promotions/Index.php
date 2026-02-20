<?php

namespace App\Livewire\Web\Promotions;

use Livewire\Component;

class Index extends Component
{

    protected $promotionSlug;
    public function mount($promotionSlug) {
        dd($promotionSlug);
    }
    public function render()
    {
        return view('livewire.web.promotions.index')->layout('livewire.web.layout');
    }
}
