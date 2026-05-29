<?php

namespace App\Livewire\Web\Main;

use App\Models\Content\StaticPage;
use Livewire\Component;

class StaticPages extends Component
{

    public string $page;

    public function mount(string $page): void
    {
        $this->page = $page;
    }

    public function render()
    {
        $content = StaticPage::where('url', $this->page)->where('locale', 'ka')->firstOrFail();
        return view('livewire.web.main.static-pages', [
            'content' => $content
        ])->layout('livewire.web.layout');
    }
}
