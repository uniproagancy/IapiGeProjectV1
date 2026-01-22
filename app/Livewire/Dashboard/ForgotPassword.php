<?php

namespace App\Livewire\Dashboard;

use App\Models\ForgotPasswordHash;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{

    public $email;

    public function rules(): array
    {
        return [
            'email' => 'required|exists:db_users,email'
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'exists' => 'ელ-ფოსტა არასწორია!',
            'email.email' => 'ელ-ფოსტა არასწორია!'
        ];
    }

    public function forgotPassword()
    {
        $this->validate();
        $user = User::where([
            'email' => $this->email,
        ])->first();
        ForgotPasswordHash::where('user_id', $user->id)->delete();
        $forgot_password = ForgotPasswordHash::create([
            'hash' => Str::random(40),
            'user_id' => $user->id,
        ]);
        $resetLink = route('dashboard.password.reset', ['hash' => $forgot_password->hash]);
        $this->dispatch('ui:success',
            message: 'აღდგენის ბმული გამოგზავნილია ელ-ფოსტაზე!',
            title: 'შეტყობინება',
            redirect_url: route('dashboard.login')
        );
    }

    public function render()
    {
        return view('livewire.dashboard.forgot-password')
            ->layout('livewire.dashboard.layout', [
                'blank_page' => true,
            ]);
    }
}
