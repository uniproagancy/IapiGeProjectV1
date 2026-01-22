<?php

namespace App\Livewire\Dashboard\Main;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.dashboard.main.index')
            ->layout('livewire.dashboard.layout');
    }
}
