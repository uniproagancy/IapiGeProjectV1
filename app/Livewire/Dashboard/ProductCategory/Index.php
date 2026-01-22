<?php

namespace App\Livewire\Dashboard\ProductCategory;

use App\Models\ProductCategory;
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
        'order_dir'    => ['except' => 'desc'],
        'per_page'     => ['except' => 10],
        'with_trashed'  => ['except' => false],
        'show_web'  => ['except' => false],
        'status_active'  => ['except' => false],
    ];

    public function toggleActive($categoryId)
    {
        $category = ProductCategory::findOrFail($categoryId);
        $category->active = !$category->active;
        if($category->active == 0)
        {
            ProductCategory::where('parent_id', $category->id)->update([
                'show' => 0,
                'active' => 0,
            ]);
            $category->show = 0;
        }
        $category->save();
        $this->dispatch('ui:success', message: 'სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
    }

    public function toggleShow($categoryId)
    {
        $category = ProductCategory::findOrFail($categoryId);
        $category->show = !$category->show;
        $category->save();
        $this->dispatch('ui:success', message: 'Show სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
    }

    public function toggleShowOnMain($categoryId)
    {
        $category = ProductCategory::findOrFail($categoryId);
        $category->show_on_main = !$category->show_on_main;
        $category->save();
        $this->dispatch('ui:success', message: 'Show on site სტატუსი განახლდა წარმატებით!', title: 'შეტყობინება');
    }

    public function deleteModal($categoryId)
    {
        $this->dispatch('swal:deleteModal', [
            'id' => $categoryId,
            'title' => 'კატეგორიის წაშლა?',
            'icon' => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'delete'
        ]);
    }
    public function restoreModal($categoryId)
    {
        $this->dispatch('swal:restoreModal', [
            'id' => $categoryId,
            'title' => 'კატეგორიის აღდგენა?',
            'icon' => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function restore($id)
    {
        ProductCategory::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('ui:success', message: 'კატეგორია აღდგა!', title: 'შეტყობინება');
    }

    public function delete($id)
    {
        $category = ProductCategory::findOrFail($id);
        ProductCategory::where('parent_id', $category->id)->update([
            'parent_id' => 1,
        ]);
        $category->update(['active' => 0]);
        $category->delete();
        $this->dispatch('ui:success', message: 'კატეგორია წაიშალა!', title: 'შეტყობინება');
    }

    public function updating($field)
    {
        if (in_array($field, ['search_query', 'order_dir', 'per_page', 'with_trashed', 'show_web', 'status_active'])) {
            $this->resetPage();
        }
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
        $query = ProductCategory::with(['translations', 'parent'])
            ->where('parent_id', 0)
            ->when($this->search_query, fn($q) =>
                $q->whereHas('translations', fn($subQuery) =>
                    $subQuery->where('title', 'like', "%{$this->search_query}%")
                        ->orWhere('slug', 'like', "%{$this->search_query}%")
                )
            )
            ->when($this->show_web === true, fn($q) =>
                $q->where('show', $this->show_web)
            )
            ->when($this->status_active === true, fn($q) =>
                $q->where('active', $this->status_active)
            )
            ->when($this->with_trashed, fn($q) => $q->withTrashed())
            ->orderBy('id', $this->order_dir);
        $categories = $query->paginate($this->per_page);
        return view('livewire.dashboard.product-category.index', [
            'categories' => $categories,
        ])->layout('livewire.dashboard.layout');
    }
}
