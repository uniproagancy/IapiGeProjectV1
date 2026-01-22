<?php

namespace App\Livewire\Dashboard\Company;

use App\Models\Company;
use App\Models\CompanyLegalForm;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search_query = '';
    public string $order_dir = 'desc';
    public int $per_page = 5;
    public bool $with_trashed = false;
    public ?int $legal_id = null;

    public function paginationView()
    {
        return 'livewire.dashboard.partials._pagination';
    }

    protected $listeners = [
        'delete',
        'restore',
        'company-refresh' => '$refresh',
        'deleteModal',
        'restoreModal'
    ];

    protected $queryString = [
        'search_query' => ['except' => ''],
        'order_dir'    => ['except' => 'desc'],
        'per_page'     => ['except' => 10],
        'with_trashed'  => ['except' => false],
        'legal_id'      => ['except' => null],
    ];

    public function updating($field)
    {
        if (in_array($field, ['search_query', 'order_dir', 'legal_id', 'per_page', 'with_trashed'])) {
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
        $this->reset(['search_query', 'order_dir', 'legal_id', 'per_page', 'with_trashed']);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function toggleActive($companyId)
    {
        $company = Company::findOrFail($companyId);
        $company->active = !$company->active;
        $company->save();
        $this->dispatch('ui:success', message: 'კომპანიის სტატუსი დარედაქტირდა', title: 'შეტყობინება');
    }

    public function deleteModal($companyId)
    {
        $this->dispatch('swal:deleteModal', [
            'id' => $companyId,
            'title' => 'კომპანიის წაშლა?',
            'icon' => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'delete'
        ]);
    }
    public function restoreModal($companyId)
    {
        $this->dispatch('swal:restoreModal', [
            'id' => $companyId,
            'title' => 'კომპანიის აღდგენა?',
            'icon' => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function restore($id)
    {
        Company::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('ui:success', message: 'კომპანია აღდგა!', title: 'შეტყობინება');
    }

    public function delete($id)
    {
        $company = Company::findOrFail($id);
        $company->update(['active' => 0]);
        $company->delete();
        $this->dispatch('ui:success', message: 'კომპანია წაიშალა!', title: 'შეტყობინება');
    }

    public function render()
    {
        $companies = Company::query()
            ->when($this->search_query, function ($q) {
                $terms = explode(' ', $this->search_query);
                $q->where(function ($subQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $subQuery->where('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%")
                            ->orWhereHas('user', function ($userQuery) use ($term) {
                                $userQuery->where('name', 'like', "%{$term}%")
                                    ->orWhere('lastname', 'like', "%{$term}%");
                            });
                        ;
                    }
                });
            })
            ->when($this->legal_id, fn($q) => $q->where('legal_id', $this->role_id))
            ->when($this->with_trashed, fn($q) => $q->withTrashed())
            ->orderBy('id', $this->order_dir)
            ->paginate($this->per_page);
        return view('livewire.dashboard.company.index', [
            'companies' => $companies,
            'legal_forms' => CompanyLegalForm::all(),
        ])->layout('livewire.dashboard.layout');
    }
}
