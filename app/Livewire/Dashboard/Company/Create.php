<?php

namespace App\Livewire\Dashboard\Company;

use App\Models\Company;
use App\Models\CompanyLegalForm;
use App\Models\User;
use Livewire\Component;

class Create extends Component
{
    public $legal_id;
    public $name;
    public $code;
    public $email;
    public $phone;
    public $user;

    protected $listeners = [
        'company-refresh' => '$refresh',
    ];

    protected $rules = [
        'legal_id' => 'required|integer|min:1',
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:255',
        'email' => 'required|unique:db_companies,email|email|max:255',
        'phone' => 'required|unique:db_companies,email|string|max:50',
        'user' => 'required|exists:db_users,email',
    ];

    public function messages()
    {
        return [
            'required' => 'გთხოვთ შეავსოთ ყველა აუცილებელი ველი!',
            'legal_id.integer' => 'სამართლებრივი ფორმა არასწორად არის მითითებული.',
            'email.email' => 'ელ-ფოსტის ფორმატი არასწორია.',
            'email.unique' => 'ეს ელ-ფოსტა უკვე გამოყენებულია სხვა კომპანიაში.',
            'phone.unique' => 'ეს ტელეფონის ნომერი უკვე გამოყენებულია სხვა კომპანიაში.',
            'user.exists' => 'მოცემული წარმომადგენელი ვერ მოიძებნა.',
        ];
    }

    private function resetForm()
    {
        $this->reset(['name', 'code', 'phone', 'email', 'user', 'legal_id']);
    }

    public function save()
    {
        $this->validate();
        $user = User::where('email', $this->user)->first();
        Company::create([
            'user_id' => $user->id,
            'code' => $this->code,
            'name' => $this->name,
            'legal_form_id' => $this->legal_id,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);
        $this->dispatch('ui:success', message: 'კომპანია წარმატებით დაემატა!', title: 'შეტყობინება');
        $this->dispatch('create_modal_close');
        $this->dispatch('company-refresh');
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.dashboard.company.create', [
            'legals' => CompanyLegalForm::all(),
        ]);
    }
}
