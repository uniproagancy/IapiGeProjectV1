<?php

namespace App\Livewire\Web\Main;

use Livewire\Component;

class Contact extends Component
{
    public function render()
    {
        return view('livewire.web.main.contact')
            ->layout('livewire.web.layout');
    }
}
