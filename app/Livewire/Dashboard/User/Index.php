<?php

namespace App\Livewire\Dashboard\User;

use App\Models\Company;
use App\Models\User\User;
use App\Models\User\Role;

use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{

    use WithPagination;

    public string $search_query = '';
    public string $order_dir = 'desc';
    public int $per_page = 10;
    public bool $with_trashed = false;
    public ?int $role_id = null;

    public function paginationView()
    {
        return 'livewire.dashboard.partials._pagination';
    }

    protected $listeners = [
        'delete',
        'restore',
        'user-refresh' => '$refresh',
        'deleteModal',
        'restoreModal'
    ];

    protected $queryString = [
        'search_query' => ['except' => ''],
        'order_dir' => ['except' => 'desc'],
        'per_page' => ['except' => 10],
        'with_trashed' => ['except' => false],
        'role_id' => ['except' => null],
    ];

    public function updating($field)
    {
        if (in_array($field, ['search_query', 'role_id', 'with_trashed'])) {
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
        $this->reset(['search_query', 'order_dir', 'role_id', 'per_page', 'with_trashed']);
        $this->resetPage();
        $this->dispatch('filter_modal_close');
    }

    public function toggleActive($userId)
    {
        $user = User::findOrFail($userId);
        $user->active = !$user->active;
        $user->save();
        $this->dispatch('ui:success', message: 'მომხხმარებლის სტატუსი დარედაქტირდა', title: 'შეტყობინება');
    }

    public function deleteModal($userId)
    {
        $this->dispatch('swal:deleteModal', [
            'id' => $userId,
            'title' => 'მომხმარებლის წაშლა?',
            'icon' => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'delete'
        ]);
    }

    public function restoreModal($userId)
    {
        $this->dispatch('swal:restoreModal', [
            'id' => $userId,
            'title' => 'მომხმარებლის აღდგენა?',
            'icon' => 'warning',
            'confirmButtonText' => 'აღდგენა!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function restore($id)
    {
        User::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('ui:success', message: 'მომხმარებელი აღდგა!', title: 'შეტყობინება');
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->update(['active' => 0]);
        Company::where('user_id', $user->id)->delete();
        $user->delete();
        $this->dispatch('ui:success', message: 'მომხმარებელი წაიშალა!', title: 'შეტყობინება');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search_query, function ($q) {
                $terms = explode(' ', $this->search_query);
                $q->where(function ($subQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $subQuery->where(function ($inner) use ($term) {
                            $inner->where('name', 'like', "%{$term}%")
                                ->orWhere('lastname', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%")
                                ->orWhere('phone', 'like', "%{$term}%");
                        });
                    }
                });
            })
            ->when($this->role_id, fn($q) => $q->where('role_id', $this->role_id))
            ->when($this->with_trashed, fn($q) => $q->withTrashed())
            ->orderBy('id', $this->order_dir)
            ->paginate($this->per_page);
        $roles = Role::where('active', 1)->get();
        return view('livewire.dashboard.user.index', [
            'users' => $users,
            'roles' => $roles,
        ])->layout('livewire.dashboard.layout');
    }
}
