@section('page_css')
    <link rel="stylesheet" href="{{ asset('web-assets/vendor/nouislider/nouislider.min.css') }}">
@endsection
<div>
    <main class="content-wrapper">
        <nav class="container pt-3 my-3 my-md-4" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">მთავარი</a></li>
                <li class="breadcrumb-item active" aria-current="page">კატალოგი</li>
            </ol>
        </nav>
        <h1 class="h3 container mb-4 font-neue">
            {{ $currentCategory?->translation('ka')->title ?? 'პროდუქციის ჩამონათვალი' }}
        </h1>
        <section class="container mb-4">
            <div class="row">
                <div class="col-lg-9">
                    <div class="d-md-flex align-items-start">
                        <div class="h6 fs-sm fw-normal text-nowrap translate-middle-y mt-3 mb-0 me-4 font-neue">
                            ნაპოვნია <span class="fw-semibold">{{ $this->products->total() }}</span> პროდუქტი
                        </div>
                    </div>
                </div>
            </div>
            <hr class="d-lg-none my-3">
        </section>
        <section class="container pb-5 mb-sm-2 mb-md-3 mb-lg-4 mb-xl-5">
            <div class="row">
                <aside class="col-lg-3">
                    <div class="w-100 border rounded p-3 p-xl-4 mb-3 mb-xl-4">
                        <h4 class="h6 mb-3 font-neue">ფასი</h4>
                        <div class="d-flex gap-2 mb-3">
                            <input type="number"
                                   class="form-control form-control-sm"
                                   wire:model.live.debounce.500ms="priceMin"
                                   placeholder="მინ. ₾"
                                   min="0">
                            <input type="number"
                                   class="form-control form-control-sm"
                                   wire:model.live.debounce.500ms="priceMax"
                                   placeholder="მაქს. ₾"
                                   min="0">
                        </div>
                        @if($priceMin || $priceMax)
                            <button wire:click="clearPriceFilter"
                                    class="btn btn-sm btn-outline-secondary w-100">
                                ფასის გასუფთავება
                            </button>
                        @endif
                    </div>
                    <div class="offcanvas-start" id="filterSidebar">
                        <div class="offcanvas-body flex-column pt-2 py-lg-0">
                            <div class="w-100 border rounded p-3 p-xl-4 mb-3 mb-xl-4">
                                <h4 class="h6 mb-2 font-neue">
                                    {{ $currentCategory?->translation('ka')->title ?? 'კატეგორიები' }}
                                </h4>
                                @if(!$selectedParent)
                                    <ul class="list-unstyled d-block m-0">
                                        @foreach($parentCategories as $category)
                                            <li class="nav d-block pt-2 mt-1">
                                                <a class="nav-link animate-underline fw-normal p-0"
                                                   href="#"
                                                   wire:click.prevent="selectParent({{ $category->id }})">
                                                    <span class="animate-target text-truncate me-3">
                                                        {{ $category->translation('ka')->title }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <ul class="list-unstyled d-block m-0">
                                        @foreach($subCategories as $subCategory)
                                            <li class="nav d-block pt-2 mt-1">
                                                <a class="nav-link animate-underline fw-normal p-0
                                                   {{ $currentCategory?->id === $subCategory->id ? 'text-primary fw-semibold' : '' }}"
                                                   href="#"
                                                   wire:click.prevent="selectChild({{ $subCategory->id }})">
                                                    <span class="animate-target text-truncate me-3">
                                                        {{ $subCategory->translation('ka')->title }}
                                                    </span>
                                                </a>
                                            </li>
                                        @endforeach
                                        <li class="pt-3">
                                            <a href="#"
                                               wire:click.prevent="resetCategories"
                                               class="text-primary"
                                               style="font-size: 14px;">
                                                ← უკან დაბრუნება
                                            </a>
                                        </li>
                                    </ul>
                                @endif
                            </div>

                            <!-- BRAND FILTER -->
                            <div class="w-100 border rounded p-3 p-xl-4 mb-3 mb-xl-4">
                                <h4 class="h6 mb-0 font-neue">
                                    ბრენდი
                                </h4>
                                <div class="expanded" id="brandsList">
                                    @if($brands->count() > 0)
                                        <div class="d-flex flex-column gap-2 mt-3">
                                            @foreach($brands as $index => $brand)
                                                <div class="form-check"
                                                     style="{{ $index >= 12 && !$this->showAllBrands ? 'display: none;' : '' }}">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           wire:model.live="selectedBrands"
                                                           value="{{ $brand->id }}"
                                                           id="brand_{{ $brand->id }}">

                                                    <label class="form-check-label text-body-emphasis"
                                                           for="brand_{{ $brand->id }}">
                                                        {{ $brand->translation('ka')->title }}
                                                    </label>
                                                </div>
                                            @endforeach
                                            @if($brands->count() > 12)
                                                <button class="btn btn-sm btn-outline-secondary w-100 mt-3"
                                                        type="button"
                                                        wire:click="toggleShowAllBrands"
                                                        @click="$event.stopPropagation()">
                                                    @if($this->showAllBrands)
                                                        გაკეცე
                                                    @else
                                                        ყველას ნახვა ({{ $brands->count() - 12 }} მეტი)
                                                    @endif
                                                </button>
                                            @endif
                                        </div>
                                    @else
                                        <p class="text-muted small mb-0 mt-3">ბრენდები არ მოიძებნა</p>
                                    @endif
                                </div>
                            </div>
                            <div class="form-check pt-3 border-top">
                                <input type="checkbox"
                                       class="form-check-input"
                                       wire:model.live="onlyDiscounted"
                                       id="discountFilter">
                                <label class="form-check-label text-body-emphasis fw-medium"
                                       for="discountFilter">
                                    მხოლოდ ფასდაკლებული
                                </label>
                            </div>
                        </div>
                    </div>
                </aside>
                <div class="col-lg-9">
                    @if($this->products->count() > 0)
                        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-4 pb-3 mb-3">
                            @foreach($this->products as $product)
                                <div class="col" wire:key="product-{{ $product->id }}">
                                    @include('livewire.web.product.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                        <div wire:click="loadMore" wire:loading.attr="disabled"
                             class="btn btn-primary py-3 d-flex justify-content-center">
                            <span wire:loading.remove class="font-neue">მეტის ნახვა</span>
                            <span wire:loading class="spinner-border"></span>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <svg class="w-25 h-25 mx-auto text-muted mb-4" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                </path>
                            </svg>
                            <h3 class="h5 mb-2">პროდუქტები არ მოიძებნა</h3>
                            <p class="text-muted">სცადეთ სხვა ფილტრების გამოყენება</p>
                            @if($selectedBrands || $priceMin || $priceMax || $selectedSpecs)
                                <button wire:click="resetAllFilters"
                                        class="btn btn-primary mt-3">
                                    ყველა ფილტრის გასუფთავება
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </main>
</div>

@section('fb_pixel')
    @if($currentCategory && $event_id)
        <script>
            fbq('trackCustom', 'CategoryView', {
                content_name: '{{ $currentCategory->translation('ka')->title }}',
                content_category: '{{ $currentCategory->translation('ka')->slug }}',
                content_ids: {!! json_encode($products->pluck('id')->map(fn($id) => (string)$id)->toArray()) !!},
                content_type: 'product'
            }, {
                eventID: '{{ $event_id }}'
            });
        </script>
    @endif
@endsection

@section('page_scripts')
    <script src="{{ asset('web-assets/vendor/nouislider/nouislider.min.js') }}"></script>
@endsection