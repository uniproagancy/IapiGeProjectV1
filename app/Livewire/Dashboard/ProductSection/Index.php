<?php

namespace App\Livewire\Dashboard\ProductSection;

use App\Models\Product\ProductSection;
use App\Models\Product\ProductCategory;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public $sectionId = null;
    public string $title = '';
    public $category_id = null;
    public bool $show_on_home = false;
    public int $sort_order = 0;
    public bool $active = true;
    public $image;            // ახალი ატვირთვა
    public $currentImage;     // არსებული path

    protected $listeners = ['delete', 'section-refresh' => '$refresh'];

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'category_id'  => 'nullable|exists:db_product_categories,id',
            'show_on_home' => 'boolean',
            'sort_order'   => 'integer',
            'active'       => 'boolean',
            'image'        => 'nullable|image|max:2048',
        ];
    }

    public function openCreate(): void
    {
        $this->reset(['sectionId', 'title', 'category_id', 'show_on_home', 'sort_order', 'active', 'image', 'currentImage']);
        $this->active = true;
        $this->dispatch('section_modal_open');
    }

    public function edit(int $id): void
    {
        $s = ProductSection::findOrFail($id);
        $this->sectionId    = $s->id;
        $this->title        = $s->title;
        $this->category_id  = $s->category_id;
        $this->show_on_home = (bool) $s->show_on_home;
        $this->sort_order   = $s->sort_order;
        $this->active       = (bool) $s->active;
        $this->currentImage = $s->image;
        $this->image        = null;
        $this->dispatch('section_modal_open');
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'title'        => $this->title,
            'category_id'  => $this->category_id ?: null,
            'show_on_home' => $this->show_on_home,
            'sort_order'   => $this->sort_order,
            'active'       => $this->active,
        ];

        // სურათი
        if ($this->image) {
            // ძველის წაშლა
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }
            $data['image'] = $this->image->store('sections', 'public');
        }

        if ($this->sectionId) {
            ProductSection::findOrFail($this->sectionId)->update($data);
            $msg = 'სექცია განახლდა!';
        } else {
            ProductSection::create($data);
            $msg = 'სექცია შეიქმნა!';
        }

        $this->dispatch('section_modal_close');
        $this->dispatch('ui:success', message: $msg);
        $this->reset(['sectionId', 'title', 'category_id', 'show_on_home', 'sort_order', 'active', 'image', 'currentImage']);
    }

    public function deleteModal(int $id): void
    {
        $this->dispatch('swal:deleteModal', [
            'id'                => $id,
            'title'             => 'სექციის წაშლა?',
            'icon'              => 'warning',
            'confirmButtonText' => 'წაშლა!',
            'cancelButtonText'  => 'დახურვა!',
        ]);
    }

    public function delete(int $id): void
    {
        $s = ProductSection::findOrFail($id);
        if ($s->image && Storage::disk('public')->exists($s->image)) {
            Storage::disk('public')->delete($s->image);
        }
        $s->products()->detach();
        $s->delete();
        $this->dispatch('ui:success', message: 'სექცია წაიშალა!');
    }

    public function toggleActive(int $id): void
    {
        $s = ProductSection::findOrFail($id);
        $s->active = !$s->active;
        $s->save();
        $this->dispatch('ui:success', message: 'სტატუსი განახლდა!');
    }

    public function toggleHome(int $id): void
    {
        $s = ProductSection::findOrFail($id);
        $s->show_on_home = !$s->show_on_home;
        $s->save();
        $this->dispatch('ui:success', message: 'მთავარზე ჩვენება განახლდა!');
    }

    public function render()
    {
        $sections = ProductSection::with('category')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('livewire.dashboard.product-section.index', [
            'sections'   => $sections,
            'categories' => ProductCategory::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}