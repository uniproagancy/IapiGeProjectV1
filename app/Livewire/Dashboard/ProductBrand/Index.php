<?php

namespace App\Livewire\Dashboard\ProductBrand;

use App\Models\Product\ProductBrand;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{

    use WithPagination;

    public string $search_query = '';
    public string $order_dir = 'desc';
    public int $per_page = 10;
    public bool $with_trashed = false;
    public bool $show_web = false;
    public bool $status_active = false;

    public function paginationView()
    {
        return 'livewire.dashboard.partials._pagination';
    }

    protected $listeners = [
        'delete',
        'restore',
        'category-refresh' => '$refresh',
        'deleteModal',
        'restoreModal'
    ];

    protected $queryString = [
        'search_query' => ['except' => ''],
        'order_dir' => ['except' => 'desc'],
        'per_page' => ['except' => 10],
        'with_trashed' => ['except' => false],
        'show_web' => ['except' => false],
        'status_active' => ['except' => false],
    ];

    public function deleteModal($brandId)
    {
        $this->dispatch('swal:deleteModal', [
            'id' => $brandId,
            'title' => 'ბრენდის წაშლა?',
            'icon' => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'delete'
        ]);
    }

    public function restoreModal($brandId)
    {
        $this->dispatch('swal:restoreModal', [
            'id' => $brandId,
            'title' => 'ბრენდის აღდგენა?',
            'icon' => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function restore($id)
    {
        ProductBrand::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('ui:success', message: 'კატეგორია აღდგა!', title: 'შეტყობინება');
    }

    public function delete($id)
    {
        $brand = ProductBrand::findOrFail($id);
        $brand->update(['active' => 0]);
        $brand->delete();
        $this->dispatch('ui:success', message: 'კატეგორია წაიშალა!', title: 'შეტყობინება');
    }

    public function toggleActive($brandId)
    {
        $brand = ProductBrand::findOrFail($brandId);
        $brand->active = !$brand->active;
        if ($brand->active == 0) {
            $brand->show = 0;
        }
        $brand->save();
        $this->dispatch('success', message: 'სტატუსი განახლდა წარმატებით!');
    }

    public function toggleShow($categoryId)
    {
        $brand = ProductBrand::findOrFail($categoryId);
        $brand->show = !$brand->show;
        $brand->save();
        $this->dispatch('success', message: 'Show სტატუსი განახლდა წარმატებით!');
    }

    public function applyFilters()
    {
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function resetFilters()
    {
        $this->reset(['search_query', 'order_dir', 'per_page', 'with_trashed', 'show_web', 'status_active']);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function render()
    {
        $query = ProductBrand::with(['translations'])
            ->when($this->search_query, fn($q) => $q->whereHas('translations', fn($subQuery) => $subQuery->where('title', 'like', "%{$this->search_query}%")
                ->orWhere('slug', 'like', "%{$this->search_query}%")
            )
            )
            ->when($this->show_web === true, fn($q) => $q->where('show', $this->show_web)
            )
            ->when($this->status_active === true, fn($q) => $q->where('active', $this->status_active)
            )
            ->when($this->with_trashed, fn($q) => $q->withTrashed())
            ->orderBy('id', $this->order_dir);
        $brands = $query->paginate($this->per_page);
        return view('livewire.dashboard.product-brand.index', [
            'brands' => $brands,
        ])->layout('livewire.dashboard.layout');
    }
}
