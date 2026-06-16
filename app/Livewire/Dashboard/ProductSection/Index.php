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

    public $sectionId    = null;
    public string $title = '';
    public $category_id  = null;      // საბოლოო (subcategory)
    public $parent_category_id = null; // parent (select-ისთვის)
    public bool $show_on_home  = false;
    public int $sort_order     = 0;
    public bool $active        = true;
    public $image;
    public $currentImage;
    public array $subCategories = [];

    protected $listeners = ['delete', 'section-refresh' => '$refresh'];

    public function rules(): array
    {
        return [
            'title'              => 'required|string|max:255',
            'category_id'        => 'nullable|exists:db_product_categories,id',
            'parent_category_id' => 'nullable|exists:db_product_categories,id',
            'show_on_home'       => 'boolean',
            'sort_order'         => 'integer',
            'active'             => 'boolean',
            'image'              => 'nullable|image|max:2048',
        ];
    }

    public function updatedParentCategoryId($value): void
    {
        $this->subCategories = $value
            ? ProductCategory::where('parent_id', $value)
                ->where('active', 1)
                ->get()
                ->toArray()
            : [];
        $this->category_id = null;
    }

    public function openCreate(): void
    {
        $this->reset([
            'sectionId', 'title', 'category_id', 'parent_category_id',
            'show_on_home', 'sort_order', 'active', 'image', 'currentImage', 'subCategories',
        ]);
        $this->active = true;
        $this->dispatch('section_modal_open');
    }

    public function edit(int $id): void
    {
        $s = ProductSection::findOrFail($id);

        $this->sectionId    = $s->id;
        $this->title        = $s->title;
        $this->show_on_home = (bool) $s->show_on_home;
        $this->sort_order   = $s->sort_order;
        $this->active       = (bool) $s->active;
        $this->currentImage = $s->image;
        $this->image        = null;

        // კატეგორიის აღდგენა (parent + sub)
        if ($s->category_id) {
            $cat = ProductCategory::find($s->category_id);
            if ($cat) {
                if ($cat->parent_id == 0 || $cat->parent_id === null) {
                    // თუ parent-ია (ძველი მონაცემი)
                    $this->parent_category_id = $cat->id;
                    $this->category_id        = null;
                    $this->subCategories      = ProductCategory::where('parent_id', $cat->id)
                        ->where('active', 1)->get()->toArray();
                } else {
                    // ქვეკატეგორია — სწორი
                    $this->parent_category_id = $cat->parent_id;
                    $this->category_id        = $cat->id;
                    $this->subCategories      = ProductCategory::where('parent_id', $cat->parent_id)
                        ->where('active', 1)->get()->toArray();
                }
            }
        } else {
            $this->parent_category_id = null;
            $this->category_id        = null;
            $this->subCategories      = [];
        }

        $this->dispatch('section_modal_open');
    }

    public function save(): void
    {
        $this->validate();

        // category_id = subcategory (თუ არჩეულია), თორემ parent (fallback), თორემ null (global)
        $finalCategoryId = $this->category_id
            ?: ($this->parent_category_id ?: null);

        $data = [
            'title'        => $this->title,
            'category_id'  => $finalCategoryId,
            'show_on_home' => $this->show_on_home,
            'sort_order'   => $this->sort_order,
            'active'       => $this->active,
        ];

        if ($this->image) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }
            $data['image'] = $this->image->store('sections', 'public');
        }

        if ($this->sectionId) {
            $section = ProductSection::findOrFail($this->sectionId);
            $section->update($data);

            // slug განახლება
            $base           = \Illuminate\Support\Str::slug($this->title) ?: 'section';
            $section->slug  = $base . '-' . $section->id;
            $section->save();

            $msg = 'სექცია განახლდა!';
        } else {
            $section = ProductSection::create($data);

            // slug შექმნა
            $base          = \Illuminate\Support\Str::slug($this->title) ?: 'section';
            $section->slug = $base . '-' . $section->id;
            $section->save();

            $msg = 'სექცია შეიქმნა!';
        }

        $this->dispatch('section_modal_close');
        $this->dispatch('ui:success', message: $msg);
        $this->reset([
            'sectionId', 'title', 'category_id', 'parent_category_id',
            'show_on_home', 'sort_order', 'active', 'image', 'currentImage', 'subCategories',
        ]);
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
        $s         = ProductSection::findOrFail($id);
        $s->active = !$s->active;
        $s->save();
        $this->dispatch('ui:success', message: 'სტატუსი განახლდა!');
    }

    public function toggleHome(int $id): void
    {
        $s              = ProductSection::findOrFail($id);
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
            'sections'         => $sections,
            'parentCategories' => ProductCategory::where('parent_id', 0)
                ->where('active', 1)
                ->get(),
        ])->layout('livewire.dashboard.layout');
    }
}