<?php

namespace App\Livewire\Web\Promotions;

use Livewire\Component;

class Index extends Component
{

    protected $promotionSlug;
    public function render()
    {
        dd($this->promotionSlug);
        return view('livewire.web.promotions.index')->layout('livewire.web.layout');
    }
}
