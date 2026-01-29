<?php

namespace App\Livewire\Web\Main;

use Livewire\Component;

class AboutUs extends Component
{
    public function render()
    {
        return view('livewire.web.main.about-us')
            ->layout('livewire.web.layout');
    }
}
