<?php

namespace App\Livewire\Dashboard\Order;

use App\Models\Order\Order;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // ============================================
    // Properties
    // ============================================

    public string $search_query = '';
    public string $order_dir    = 'desc';
    public int    $per_page     = 25;
    public bool   $with_trashed = false;

    public $status_id   = null;
    public $payment_id  = null;

    // ============================================
    // Listeners
    // ============================================

    protected $listeners = [
        'delete',
        'restore',
        'order-refresh' => '$refresh',
    ];

    // ============================================
    // Query String
    // ============================================

    protected $queryString = [
        'search_query' => ['except' => ''],
        'order_dir'    => ['except' => 'desc'],
        'per_page'     => ['except' => 25],
        'status_id'    => ['except' => null],
        'payment_id'   => ['except' => null],
        'with_trashed' => ['except' => false],
    ];

    // ============================================
    // Pagination
    // ============================================

    public function paginationView(): string
    {
        return 'livewire.dashboard.partials._pagination';
    }

    // ============================================
    // Delete / Restore
    // ============================================

    public function deleteModal(int $orderId): void
    {
        $this->dispatch('swal:deleteModal', [
            'id'                => $orderId,
            'title'             => 'შეკვეთის წაშლა?',
            'icon'              => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText'  => 'დახურვა!',
        ]);
    }

    public function restoreModal(int $orderId): void
    {
        $this->dispatch('swal:restoreModal', [
            'id'                => $orderId,
            'title'             => 'შეკვეთის აღდგენა?',
            'icon'              => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText'  => 'დახურვა!',
        ]);
    }

    public function delete(int $id): void
    {
        $order = Order::findOrFail($id);
        $order->update(['status_id' => 4]);
        $order->delete();
        $this->dispatch('ui:success', message: 'შეკვეთა წაიშალა!');
    }

    public function restore(int $id): void
    {
        $order = Order::withTrashed()->findOrFail($id);
        $order->update(['status_id' => 1]);
        $order->restore();
        $this->dispatch('ui:success', message: 'შეკვეთა აღდგა!');
    }

    // ============================================
    // Filters
    // ============================================

    public function applyFilters(): void
    {
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search_query', 'order_dir', 'per_page',
            'status_id', 'payment_id', 'with_trashed',
        ]);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    // ============================================
    // Render
    // ============================================

    public function render()
    {
        $orders = Order::with(['user', 'items', 'orderStatus', 'paymentStatus', 'payment'])
            ->when($this->search_query, fn($q) =>
            $q->where('id', 'like', "%{$this->search_query}%")
                ->orWhereHas('user', fn($u) =>
                $u->where('name', 'like', "%{$this->search_query}%")
                    ->orWhere('phone', 'like', "%{$this->search_query}%")
                )
            )
            ->when($this->status_id, fn($q) =>
            $q->where('status_id', $this->status_id)
            )
            ->when($this->payment_id, fn($q) =>
            $q->where('payment_id', $this->payment_id)
            )
            ->when($this->with_trashed, fn($q) =>
            $q->withTrashed()
            )
            ->orderBy('id', $this->order_dir)
            ->paginate($this->per_page);

        return view('livewire.dashboard.order.index', [
            'orders' => $orders,
        ])->layout('livewire.dashboard.layout');
    }
}