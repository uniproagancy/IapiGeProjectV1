<?php

namespace App\Livewire\Dashboard\DeliveryMethod;

use App\Models\DeliveryMethod;
use Livewire\Component;

class Index extends Component
{

    public function toggleActive($id)
    {
        $method = DeliveryMethod::findOrFail($id);
        $method->active = !$method->active;
        $method->save();
        $this->dispatch('success', message: 'სტატუსი განახლდა წარმატებით!');
    }

    public function toggleShow($id)
    {
        $method = DeliveryMethod::findOrFail($id);
        $method->show = !$method->show;
        $method->save();
        $this->dispatch('success', message: 'Show სტატუსი განახლდა წარმატებით!');
    }

    public function render()
    {
        $delivery_methods = DeliveryMethod::with(['translations'])->get();
        return view('livewire.dashboard.delivery.index', [
            'delivery_methods' => $delivery_methods,
        ])->layout('livewire.dashboard.layout');
    }
}
