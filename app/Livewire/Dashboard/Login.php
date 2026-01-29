<?php

namespace App\Livewire\Dashboard;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class Login extends Component
{

    public $email;
    public $password;
    public $remember_me = false;

    public function rules(): array
    {
        return [
            'email' => 'required|exists:db_users,email',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'exists' => 'ელ-ფოსტა არასწორია!',
        ];
    }

    public function login()
    {
        $this->validate();
        if (!Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember_me)) {
            $this->dispatch('ui:error', message: 'ელ-ფოსტა ან პაროლი არასწორია!', title: 'დაფიქსირდა შეცდომა');
            return;
        };
        return Redirect::route('dashboard.main');
    }

    public function render()
    {
        return view('livewire.dashboard.login')
            ->layout('livewire.dashboard.layout', [
                'blank_page' => true,
            ]);
    }
}
