<?php

namespace App\Livewire\Web\Auth;

use App\Services\Sender\SmsOffice;
use Livewire\Component;
use App\Models\User\User;
use Illuminate\Support\Facades\Hash;

class ForgotModal extends Component
{
    public $phone = '';

    protected $rules = [
        'phone' => 'required|nullable|string|max:20',
    ];

    protected $messages = [
        'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
    ];

    public function forgot()
    {
        $this->validate();
        $password = rand(100000, 999999);
        $user = User::where('phone', $this->phone)->first();
        $user->update(['password' => Hash::make($password)]);
        (new \App\Services\Sender\SmsOffice)->send($this->phone, 'თქვენი დროებითი პაროლი '.$password);
        $this->dispatch('close-modal', 'forgotModal');
        $this->dispatch('ui:success', message: 'დროებითი პაროლი გამოგზავნილია თქვენს ელ-ფოსტაზე!');
        return redirect('/');
    }

    public function render()
    {
        return view('livewire.web.auth.forgot-modal');
    }
}