<?php

namespace App\Livewire\Web\Auth;

use Livewire\Component;
use App\Models\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\Facebook\FacebookPixelService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RegisterModal extends Component
{
    public $name = '';
    public $lastname = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $password_confirmation = '';
    public $terms = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'lastname' => 'required|string|max:255',
        'email' => 'required|email|unique:db_users,email',
        'phone' => 'required|nullable|string|max:20|unique:db_users,phone',
        'password' => 'required|min:8|confirmed',
        'password_confirmation' => 'required|min:8',
        'terms' => 'accepted',
    ];

    protected $messages = [
        'name.required' => 'სახელი აუცილებელია',
        'lastname.required' => 'გვარი აუცილებელია',
        'email.required' => 'ელფოსტა აუცილებელია',
        'email.email' => 'არასწორი ელფოსტის ფორმატი',
        'email.unique' => 'ელფოსტა უკვე რეგისტრირებულია',
        'password.required' => 'პაროლი აუცილებელია',
        'password.min' => 'პაროლი უნდა იყოს მინიმუმ 6 სიმბოლო',
        'password.confirmed' => 'პაროლები არ ემთხვევა',
        'terms.accepted' => 'თქვენ უნდა დაეთანხმოთ წესებსა და პირობებს',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => Hash::make($this->password),
            'email_verified_at' => now()
        ]);

        // ✅ Track CompleteRegistration Event
        $eventId = 'cr_' . time() . '_' . Str::random(6);

        Log::info('🎉 User registered successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'event_id' => $eventId,
        ]);

        // ✅ Track with custom user data (before auto-login)
        app(FacebookPixelService::class)->trackCompleteRegistration(
            [
                'email' => $this->email,
                'phone' => $this->phone,
                'first_name' => $this->name,
                'last_name' => $this->lastname,
            ],
            [],
            $eventId
        );

        // ✅ Dispatch event to browser for client-side tracking
        $this->dispatch('registration-completed', [
            'event_id' => $eventId,
            'user_email' => $this->email,
            'user_phone' => $this->phone,
            'user_name' => $this->name,
            'user_lastname' => $this->lastname,
        ]);

        // TODO SMS SENDER
        // TODO MAIL SENDER

        $this->dispatch('close-modal', 'registerModal');
        $this->dispatch('ui:success', message: 'თქვენ წარმატებით დარეგისტრირდით!');

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.web.auth.register-modal');
    }
}