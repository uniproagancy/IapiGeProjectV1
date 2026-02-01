<?php

namespace App\Livewire\Dashboard\User;

use App\Models\User\Role;
use App\Models\User\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class Create extends Component
{

    public $name;
    public $lastname;
    public $email;
    public $phone;
    public $birthday_date;
    public $role_id;

    protected $listeners = [
        'user-refresh' => '$refresh',
    ];

    private function resetForm()
    {
        $this->reset(['name', 'lastname', 'phone', 'email', 'role_id']);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2',
            'lastname' => 'required|string|min:2',
            'email' => 'required|email|unique:db_users,email',
            'phone' => 'required|string|max:20',
            'birthday_date' => 'nullable|date',
            'role_id' => 'required|exists:db_roles,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'გთხოვთ მიუთითოთ სახელი!',
            'lastname.required' => 'გთხოვთ მიუთითოთ გვარი!',
            'email.required' => 'გთხოვთ მიუთითოთ ელ.ფოსტა!',
            'email.email' => 'ელ.ფოსტის ფორმატი არასწორია!',
            'email.unique' => 'ასეთი ელ.ფოსტა უკვე არსებობს!',
            'role_id.required' => 'გთხოვთ აირჩიოთ როლი!',
        ];
    }

    public function save()
    {
        $this->validate();
        $password = Str::password(8, true, true, false);
        $user = User::create([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($password),
            'birthday_date' => $this->birthday_date,
            'role_id' => $this->role_id,
        ]);
        // TODO MAIL SENDER
        $this->dispatch('ui:success', message: 'მომხმარებელი წარმატებით დაემატა!', title: 'შეტყობინება');
        $this->dispatch('create_modal_close');
        $this->dispatch('user-refresh');
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.dashboard.user.create', [
            'roles' => Role::where('active', 1)->get(),
        ]);
    }
}
