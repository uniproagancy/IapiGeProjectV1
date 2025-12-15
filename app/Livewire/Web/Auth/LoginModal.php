<?php

namespace App\Livewire\Web\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginModal extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    protected $rules = [
        'email' => 'required',
        'password' => 'required',
    ];

    protected $messages = [
        'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
    ];

    public function login()
    {
        $this->validate();
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->dispatch('close-modal', 'loginModal');
            return redirect()->intended('/');
        } else{
            $this->dispatch('ui:error', message: 'ელ-ფოსტა ან პაროლი არასწორია!');
        }
    }

    public function render()
    {
        return view('livewire.web.auth.login-modal');
    }
}