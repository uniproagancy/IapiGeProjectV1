<?php

namespace App\Livewire\Dashboard;

use App\Models\ForgotPasswordHash;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class ResetPassword extends Component
{

    public $hash;
    public $password;
    public $password_confirmation;

    public $user;

    public function mount($hash)
    {
        $forgot = ForgotPasswordHash::where('hash', $hash)->first();
        if (!$forgot) {
            abort(404);
        }
        $this->user = User::find($forgot->user_id);
        if (!$this->user) {
            $this->dispatch('error', message: 'მომხმარებელი ვერ მოიძებნა!');
            return Redirect::route('dashboard.login');
        }
        $this->hash = $hash;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'min' => 'პაროლი უნდა შეიცავდეს მინიმუმ 6 სიმბოლოს!',
            'confirmed' => 'პაროლები არ ემთხვევა ერთმანეთს!',
        ];
    }

    public function resetPassword()
    {
        $this->validate();
        $this->user->update([
            'password' => Hash::make($this->password),
        ]);
        ForgotPasswordHash::where('hash', $this->hash)->delete();
        $this->dispatch('ui:success',
            message: 'პაროლი წარმატებით შეიცვალა!',
            title: 'შეტყობინება',
            redirect_url: route('dashboard.login')
        );
    }

    public function render()
    {
        return view('livewire.dashboard.reset-password')
            ->layout('livewire.dashboard.layout', [
                'blank_page' => true,
            ]);
    }
}
