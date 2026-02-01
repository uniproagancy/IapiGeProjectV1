<?php

namespace App\Livewire\Dashboard\User;

use App\Models\User\Role;
use App\Models\User\User;
use Livewire\Component;

class View extends Component
{
    public $user_id;
    public $user;
    public $name;
    public $lastname;
    public $email;
    public $phone;
    public $birthday_date;
    public $role_id;
    public $status;

    public function mount($user_id)
    {
        $this->user = User::findOrFail($user_id);
        $this->name = $this->user->name;
        $this->lastname = $this->user->lastname;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->birthday_date = $this->user->birthday_date;
        $this->role_id = $this->user->role_id;
        $this->status = $this->user->status;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'lastname' => 'nullable|string|min:2',
            'email' => 'required|email|unique:db_users,email,' . $this->user->id,
            'phone' => 'nullable|string|max:30|unique:db_users,phone,' . $this->user->id,
            'birthday_date' => 'nullable|date',
            'role_id' => 'required|exists:db_roles,id',
            'status' => 'boolean',
        ];
    }

    public function toggleActive($userId)
    {
        $user = User::findOrFail($userId);
        $user->active = !$user->active;
        $user->save();
        $this->dispatch('ui:success', message: 'მომხხმარებლის სტატუსი დარედაქტირდა', title: 'შეტყობინება');
    }

    public function render()
    {
        $roles = Role::where('active', 1)->get();
        return view('livewire.dashboard.user.view', [
            'roles' => $roles,
            'user' => User::findOrFail($this->user_id),
        ])->layout('livewire.dashboard.layout');
    }
}
