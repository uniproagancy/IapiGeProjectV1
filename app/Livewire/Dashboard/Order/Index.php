<?php

namespace App\Livewire\Dashboard\Order;

use App\Models\Order;
use Livewire\Component;

class Index extends Component
{

    protected $listeners = [
        'delete',
        'restore',
        'order-refresh' => '$refresh',
        'deleteModal',
        'restoreModal'
    ];

    public function deleteModal($orderId)
    {
        $this->dispatch('swal:deleteModal', [
            'id' => $orderId,
            'title' => 'შეკვეთის წაშლა?',
            'icon' => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'delete'
        ]);
    }
    public function restoreModal($orderId)
    {
        $this->dispatch('swal:restoreModal', [
            'id' => $orderId,
            'title' => 'პროდუქტის აღდგენა?',
            'icon' => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function restore($id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        $order->update(['status_id' => 1]);
        $order->restore();
        $this->dispatch('ui:success', message: 'შეკვეთა აღდგა!', title: 'შეტყობინება');
    }

    public function delete($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status_id' => 4]);
        $order->delete();
        $this->dispatch('ui:success', message: 'შეკვეთა წაიშალა!', title: 'შეტყობინება');
    }

    public function render()
    {
        return view('livewire.dashboard.order.index', [
            'orders' => Order::withTrashed()->get()
        ])->layout('livewire.dashboard.layout');
    }
}
