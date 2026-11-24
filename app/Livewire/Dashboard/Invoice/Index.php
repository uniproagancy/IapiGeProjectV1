<?php

namespace App\Livewire\Dashboard\Invoice;

use App\Models\Invoice\Invoice;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function paginationView(): string
    {
        return 'livewire.dashboard.partials._pagination';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->search);

        $invoices = Invoice::query()
            ->with('author')
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_code', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%");
            }))
            ->latest('id')
            ->paginate(25);

        return view('livewire.dashboard.invoice.index', compact('invoices'))
            ->layout('livewire.dashboard.layout');
    }
}
