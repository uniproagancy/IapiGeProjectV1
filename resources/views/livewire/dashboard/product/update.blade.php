@section('page_css')
    <link rel="stylesheet" type="text/css"
          href="{{ asset('dashboard-assets/vendors/css/file-uploaders/dropzone.min.css') }}">
    <link rel="stylesheet" type="text/css"
          href="{{ asset('dashboard-assets/css/plugins/forms/form-file-uploader.css') }}">
@endsection

<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <form wire:submit.prevent="save">
            <div class="content-body">
                <div class="row">

                    {{-- მარცხენა კოლონა --}}
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">პარამეტრები</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">

                                    {{-- კატეგორია --}}
                                    <div class="col-md-12 mb-1">
                                        <label class="form-label">კატეგორია <span class="text-danger">*</span></label>
                                        <select class="form-select @error('category_id') border-danger is-invalid @enderror"
                                                wire:model="category_id">
                                            <option value="">აირჩიეთ კატეგორია</option>
                                            @foreach($categories as $category)
                                                <optgroup label="{{ $category->translations->where('locale','ka')->first()?->title }}">
                                                    @foreach($category->children as $child)
                                                        <option value="{{ $child->id }}">
                                                            {{ $child->translations->where('locale','ka')->first()?->title }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- ბრენდი --}}
                                    <div class="col-12 mb-1">
                                        <label class="form-label">ბრენდი <span class="text-danger">*</span></label>
                                        <select class="form-select @error('brand_id') border-danger is-invalid @enderror"
                                                wire:model="brand_id">
                                            <option value="">აირჩიეთ ბრენდი</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}">
                                                    {{ $brand->translations->where('locale','ka')->first()?->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- მომწოდებელი --}}
                                    <div class="col-12 mb-1">
                                        <label class="form-label">მომწოდებელი <span class="text-danger">*</span></label>
                                        <select class="form-select @error('supplier_id') border-danger is-invalid @enderror"
                                                wire:model="supplier_id">
                                            <option value="">აირჩიეთ მომწოდებელი</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">
                                                    {{ $supplier->translations->where('locale','ka')->first()?->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('supplier_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- ფასები --}}
                                    <div class="col-4 mb-1">
                                        <label class="form-label">მომწოდ. ფასი</label>
                                        <input type="number"
                                               step="0.01"
                                               class="form-control"
                                               wire:model="dealer_price"
                                               placeholder="0.00">
                                    </div>
                                    <div class="col-4 mb-1">
                                        <label class="form-label">ფასი <span class="text-danger">*</span></label>
                                        <input type="number"
                                               step="0.01"
                                               class="form-control @error('regular_price') border-danger is-invalid @enderror"
                                               wire:model="regular_price"
                                               placeholder="0.00">
                                        @error('regular_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-4 mb-1">
                                        <label class="form-label">ფასდაკლება</label>
                                        <input type="number"
                                               step="0.01"
                                               class="form-control"
                                               wire:model="discount_price"
                                               placeholder="0.00">
                                    </div>

                                    {{-- SKU --}}
                                    <div class="col-12 mb-1">
                                        <label class="form-label">SKU</label>
                                        <input type="text"
                                               class="form-control"
                                               wire:model="sku"
                                               placeholder="SKU-123">
                                    </div>

                                    {{-- ნაშთი --}}
                                    <div class="col-12 mb-1">
                                        <label class="form-label">ნაშთი</label>
                                        <input type="number"
                                               class="form-control"
                                               wire:model="quantity"
                                               min="0">
                                    </div>

                                    {{-- სტატუსი --}}
                                    <div class="col-12 mb-1">
                                        <label class="form-label">სტატუსი</label>
                                        <select class="form-select" wire:model="active">
                                            <option value="1">აქტიური</option>
                                            <option value="0">არააქტიური</option>
                                        </select>
                                    </div>

                                    {{-- checkboxები --}}
                                    <div class="col-12 mb-2">
                                        <div class="demo-inline-spacing">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="in_stock"
                                                       wire:model="in_stock">
                                                <label class="form-check-label" for="in_stock">მარაგშია</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="preorder"
                                                       wire:model="preorder">
                                                <label class="form-check-label" for="preorder">წინასწარი შეკვეთით</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="draft"
                                                       wire:model="draft">
                                                <label class="form-check-label" for="draft">Draft</label>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- შენახვა --}}
                                    <div class="col-12">
                                        <button type="submit"
                                                class="btn btn-success w-100 mt-2"
                                                wire:loading.attr="disabled">
                                            <span wire:loading.remove>განახლება</span>
                                            <span wire:loading>
                                                <span class="spinner-border spinner-border-sm"></span>
                                                ინახება...
                                            </span>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- მარჯვენა კოლონა --}}
                    <div class="col-md-8">

                        {{-- ტექსტური ინფო --}}
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">ინფორმაცია პროდუქტზე</h4>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#description_ka">
                                            KA <span class="text-danger">*</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#description_en">EN</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#description_ru">RU</a>
                                    </li>
                                </ul>
                                <div class="tab-content pt-2">

                                    {{-- KA --}}
                                    <div class="tab-pane active" id="description_ka">
                                        <div class="row">
                                            <div class="col-md-12 mb-1">
                                                <label class="form-label">
                                                    დასახელება (ქართულად) <span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                       class="form-control @error('title_ka') border-danger is-invalid @enderror"
                                                       wire:model="title_ka">
                                                @error('title_ka')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-12 mb-1">
                                                <label class="form-label">აღწერა (ქართულად)</label>
                                                <textarea class="form-control"
                                                          rows="6"
                                                          wire:model="description_ka"></textarea>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Meta Keywords (ქართულად)</label>
                                                <input type="text" class="form-control" wire:model="keywords_ka">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- EN --}}
                                    <div class="tab-pane" id="description_en">
                                        <div class="row">
                                            <div class="col-md-12 mb-1">
                                                <label class="form-label">დასახელება (ინგლისურად)</label>
                                                <input type="text" class="form-control" wire:model="title_en">
                                            </div>
                                            <div class="col-md-12 mb-1">
                                                <label class="form-label">აღწერა (ინგლისურად)</label>
                                                <textarea class="form-control" rows="6" wire:model="description_en"></textarea>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Meta Keywords (ინგლისურად)</label>
                                                <input type="text" class="form-control" wire:model="keywords_en">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- RU --}}
                                    <div class="tab-pane" id="description_ru">
                                        <div class="row">
                                            <div class="col-md-12 mb-1">
                                                <label class="form-label">დასახელება (რუსულად)</label>
                                                <input type="text" class="form-control" wire:model="title_ru">
                                            </div>
                                            <div class="col-md-12 mb-1">
                                                <label class="form-label">აღწერა (რუსულად)</label>
                                                <textarea class="form-control" rows="6" wire:model="description_ru"></textarea>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Meta Keywords (რუსულად)</label>
                                                <input type="text" class="form-control" wire:model="keywords_ru">
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- სურათები --}}
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">სურათები</h4>
                            </div>
                            <div class="card-body">

                                {{-- მთავარი სურათი --}}
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">მთავარი სურათი</label>

                                    {{-- არსებული სურათი --}}
                                    @if($current_main_image)
                                        <div class="mb-2">
                                            <img src="{{ asset('storage/' . $current_main_image) }}"
                                                 alt="main image"
                                                 style="max-height: 120px; border-radius: 6px;">
                                            <div class="text-muted small mt-1">ახალი სურათის ატვირთვა შეცვლის არსებულს</div>
                                        </div>
                                    @endif

                                    <input type="file"
                                           class="form-control @error('main_image') border-danger is-invalid @enderror"
                                           wire:model="main_image"
                                           accept="image/*">
                                    @error('main_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror

                                    {{-- ახალი სურათის preview --}}
                                    @if($main_image)
                                        <div class="mt-2">
                                            <img src="{{ $main_image->temporaryUrl() }}"
                                                 alt="preview"
                                                 style="max-height: 120px; border-radius: 6px; border: 2px solid #28a745;">
                                            <div class="text-success small mt-1">ახალი სურათი</div>
                                        </div>
                                    @endif
                                </div>

                                {{-- არსებული დამატებითი სურათები --}}
                                @if(!empty($existing_images))
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">არსებული სურათები</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($existing_images as $img)
                                                <div class="position-relative">
                                                    <img src="{{ asset('storage/' . $img['path']) }}"
                                                         alt="image"
                                                         style="height: 80px; width: 80px; object-fit: cover; border-radius: 6px;">
                                                    <button type="button"
                                                            class="btn btn-danger btn-sm position-absolute top-0 end-0"
                                                            style="padding: 1px 5px; font-size: 11px;"
                                                            wire:click="deleteImage({{ $img['id'] }})"
                                                            wire:confirm="სურათი წაიშლება, დარწმუნებული ხართ?">
                                                        ✕
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- ახალი დამატებითი სურათები --}}
                                <div class="col-md-12">
                                    <label class="form-label">დამატებითი სურათების დამატება</label>
                                    <input type="file"
                                           class="form-control @error('additional_images.*') border-danger is-invalid @enderror"
                                           wire:model="additional_images"
                                           accept="image/*"
                                           multiple>
                                    @error('additional_images.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror

                                    {{-- preview --}}
                                    @if(!empty($additional_images))
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            @foreach($additional_images as $img)
                                                <img src="{{ $img->temporaryUrl() }}"
                                                     alt="preview"
                                                     style="height: 80px; width: 80px; object-fit: cover; border-radius: 6px; border: 2px solid #28a745;">
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </div>
</div>