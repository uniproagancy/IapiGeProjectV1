@section('seo')
    <title>
        {{ ($currentCategory?->translation('ka')?->title ? $currentCategory->translation('ka')->title . ' — შეიძინე იაფად | iapi.ge' : 'პროდუქციის ჩამონათვალი — შეიძინე იაფად | iapi.ge') }}
    </title>
    <meta name="keywords" content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

@section('page_css')
    <link rel="stylesheet" href="{{ asset('web-assets/vendor/nouislider/nouislider.min.css') }}">
    <style>
        [x-cloak] { display: none !important; }
        .filter-section-title {
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 10px 0;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }
        .filter-section-title.has-selected { color: #0d6efd; }
        .filter-body { padding: 8px 0 4px 0; }
        .filter-body .form-check { padding: 3px 0 3px 1.5em; margin: 0; }
        .filter-body .form-check-label { font-size: 13px; cursor: pointer; color: #444; }
        .filter-body .form-check-input { margin-top: 3px; }
        .filter-block { margin-bottom: 6px; }
        .filter-selected-badge {
            font-size: 11px;
            padding: 1px 6px;
            border-radius: 10px;
            background: #0d6efd;
            color: #fff;
            margin-left: 6px;
        }
        .filter-show-more {
            font-size: 12px;
            color: #0d6efd;
            background: none;
            border: none;
            padding: 2px 0;
            cursor: pointer;
            text-decoration: underline;
        }
        .mobile-filter-modal {
            position: fixed;
            inset: 0;
            background: #fff;
            z-index: 1050;
            transform: translateX(100%);
            transition: transform .3s ease;
            display: flex;
            flex-direction: column;
        }
        .mobile-filter-modal.show { transform: translateX(0); }
        .mobile-filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .mobile-filter-body { flex: 1; overflow-y: auto; padding: 12px 16px; }
        .mobile-filter-footer {
            display: flex;
            gap: 10px;
            padding: 12px 16px;
            border-top: 1px solid rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
    </style>
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
            {{ $currentCategory?->translation('ka')?->title ?? 'პროდუქციის ჩამონათვალი' }}
        </h1>
        <section class="container mb-4">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="h6 fs-sm fw-normal text-nowrap mb-0 font-neue">
                            ნაპოვნია <span class="fw-semibold">{{ $this->products->total() }}</span> პროდუქტი
                        </div>
                        <div class="d-flex align-items-center">
                            <label class="fs-sm text-muted me-2 mb-0 text-nowrap d-none d-sm-inline">დალაგება:</label>
                            <select class="form-select form-select-sm" style="min-width: 190px;"
                                    wire:model.live="sort">
                                <option value="newest">უახლესი</option>
                                <option value="price_asc">ფასი: დაბლიდან მაღლა</option>
                                <option value="price_desc">ფასი: მაღლიდან დაბლა</option>
                                <option value="oldest">ძველი</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="d-lg-none my-3">
        </section>

        <section class="container pb-5 mb-sm-2 mb-md-3 mb-lg-4 mb-xl-5">
            <div class="row">
                <aside class="col-lg-3 d-none d-lg-block">

                    {{-- ფასის ფილტრი --}}
                    <div class="filter-block">
                        <div class="filter-section-title {{ ($priceMin || $priceMax) ? 'has-selected' : '' }}"
                             data-bs-toggle="collapse"
                             data-bs-target="#filter_price">
                            <span>
                                ფასი
                                @if($priceMin || $priceMax)
                                    <span class="filter-selected-badge">✓</span>
                                @endif
                            </span>
                            <i class="ci-chevron-down fs-sm"></i>
                        </div>
                        <div class="collapse show" id="filter_price">
                            <div class="filter-body">
                                <div class="d-flex gap-2 mb-2">
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
                                            class="btn btn-sm btn-outline-secondary w-100 mt-1"
                                            style="font-size: 12px;">
                                        გასუფთავება
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="offcanvas-start" id="filterSidebar">
                        <div class="offcanvas-body flex-column pt-2 py-lg-0">

                            {{-- კატეგორიები --}}
                            <div class="filter-block">
                                <div class="filter-section-title"
                                     data-bs-toggle="collapse"
                                     data-bs-target="#filter_categories">
                                    <span>{{ $currentCategory?->translation('ka')?->title ?? 'კატეგორიები' }}</span>
                                    <i class="ci-chevron-down fs-sm"></i>
                                </div>
                                <div class="collapse show" id="filter_categories">
                                    <div class="filter-body">
                                        @if(!$selectedParent)
                                            <ul class="list-unstyled m-0">
                                                @foreach($parentCategories as $category)
                                                    <li class="py-1">
                                                        <a class="text-body text-decoration-none"
                                                           style="font-size: 13px;"
                                                           href="#"
                                                           wire:click.prevent="selectParent({{ $category->id }})">
                                                            {{ $category->translation('ka')?->title ?? '—' }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <ul class="list-unstyled m-0">
                                                @foreach($subCategories as $subCategory)
                                                    <li class="py-1">
                                                        <a class="text-decoration-none {{ $currentCategory?->id === $subCategory->id ? 'text-primary fw-semibold' : 'text-body' }}"
                                                           style="font-size: 13px;"
                                                           href="#"
                                                           wire:click.prevent="selectChild({{ $subCategory->id }})">
                                                            {{ $subCategory->translation('ka')?->title ?? '—' }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                                <li class="pt-2">
                                                    <a href="#"
                                                       wire:click.prevent="resetCategories"
                                                       class="text-primary"
                                                       style="font-size: 12px;">
                                                        ← უკან
                                                    </a>
                                                </li>
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- ბრენდი --}}
                            @if($brands->count() > 0)
                                <div class="filter-block">
                                    <div class="filter-section-title {{ !empty($selectedBrands) ? 'has-selected' : '' }}"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#filter_brands">
                                        <span>
                                            ბრენდი
                                            @if(!empty($selectedBrands))
                                                <span class="filter-selected-badge">{{ count($selectedBrands) }}</span>
                                            @endif
                                        </span>
                                        <i class="ci-chevron-down fs-sm"></i>
                                    </div>
                                    <div class="collapse show" id="filter_brands">
                                        <div class="filter-body">
                                            @foreach($brands as $index => $brand)
                                                <div class="form-check"
                                                     @if($index >= 5 && !$this->showAllBrands) style="display:none;" @endif>
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           wire:model.live="selectedBrands"
                                                           value="{{ $brand->id }}"
                                                           id="brand_{{ $brand->id }}">
                                                    <label class="form-check-label"
                                                           for="brand_{{ $brand->id }}">
                                                        {{ $brand->translation('ka')?->title ?? ('ბრენდი #' . $brand->id) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                            @if($brands->count() > 5)
                                                <button class="filter-show-more mt-2"
                                                        type="button"
                                                        wire:click="toggleShowAllBrands">
                                                    @if($this->showAllBrands)
                                                        ნაკლები ↑
                                                    @else
                                                        მეტის ნახვა ({{ $brands->count() - 5 }}) ↓
                                                    @endif
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- სპეციფიკაციების ფილტრი --}}
                            @if(!empty($specificationSections) && $specificationSections->count() > 0)
                                @foreach($specificationSections as $specName => $values)
                                    @php
                                        $selectedInSection = collect($selectedSpecs)->filter(
                                            fn($s) => str_starts_with($s, $specName . '::')
                                        );
                                        $hasSelected = $selectedInSection->count() > 0;
                                        $specKey     = 'spec_' . md5($specName);
                                        $valuesCount = count($values);
                                    @endphp
                                    <div class="filter-block">
                                        <div class="filter-section-title {{ $hasSelected ? 'has-selected' : '' }}"
                                             data-bs-toggle="collapse"
                                             data-bs-target="#{{ $specKey }}">
                                            <span>
                                                {{ $specName }}
                                                @if($hasSelected)
                                                    <span class="filter-selected-badge">{{ $selectedInSection->count() }}</span>
                                                @endif
                                            </span>
                                            <i class="ci-chevron-down fs-sm"></i>
                                        </div>
                                        <div class="collapse show" id="{{ $specKey }}">
                                            <div class="filter-body" x-data="{ open: {{ $hasSelected ? 'true' : 'false' }} }">
                                                @foreach($values as $i => $item)
                                                    @php
                                                        $isSelected = $selectedInSection->contains($specName . '::' . $item->value);
                                                    @endphp
                                                    <div class="form-check"
                                                         @if($i >= 5) x-show="open || {{ $isSelected ? 'true' : 'false' }}" x-cloak @endif>
                                                        <input type="checkbox"
                                                               class="form-check-input"
                                                               wire:model.live="selectedSpecs"
                                                               value="{{ $specName }}::{{ $item->value }}"
                                                               id="spec_{{ md5($specName . $item->value) }}">
                                                        <label class="form-check-label"
                                                               for="spec_{{ md5($specName . $item->value) }}">
                                                            {{ $item->value }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                                @if($valuesCount > 5)
                                                    <button class="filter-show-more mt-2"
                                                            type="button"
                                                            @click="open = !open">
                                                        <span x-show="!open">მეტის ნახვა ({{ $valuesCount - 5 }}) ↓</span>
                                                        <span x-show="open" x-cloak>ნაკლები ↑</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            {{-- მხოლოდ ფასდაკლებული --}}
                            <div class="filter-block pt-2">
                                <div class="form-check">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           wire:model.live="onlyDiscounted"
                                           id="discountFilter">
                                    <label class="form-check-label fw-medium"
                                           style="font-size: 13px;"
                                           for="discountFilter">
                                        მხოლოდ ფასდაკლებული
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                </aside>
                <div class="col-lg-9">
                    @if($currentCategory && isset($categorySections) && $categorySections->count() > 0)
                        @include('livewire.web.partials.sections-carousel', [
                            'sections' => $categorySections,
                        ])
                        <span class="mb-1"></span>
                    @endif
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
                                <button wire:click="resetAllFilters" class="btn btn-primary mt-3">
                                    ყველა ფილტრის გასუფთავება
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Mobile ფილტრის ღილაკი (fixed) --}}
        <button type="button"
                class="btn btn-dark d-lg-none position-fixed shadow"
                style="bottom: 80px; right: 16px; z-index: 1045; border-radius: 50px; padding: 12px 20px;"
                onclick="document.getElementById('mobileFilterModal').classList.add('show'); document.body.style.overflow='hidden';">
            <i class="ci-filter me-1"></i> ფილტრი
            @php
                $activeCount = (int)(!empty($priceMin) || !empty($priceMax))
                             + count((array)$selectedBrands)
                             + count((array)$selectedSpecs)
                             + (int)$onlyDiscounted;
            @endphp
            @if($activeCount > 0)
                <span class="badge bg-light text-dark ms-1">{{ $activeCount }}</span>
            @endif
        </button>

        {{-- Mobile ფილტრის მოდალი --}}
        <div id="mobileFilterModal" class="mobile-filter-modal d-lg-none">
            <div class="mobile-filter-header">
                <h5 class="m-0 font-neue">ფილტრი</h5>
                <button type="button" class="btn-close"
                        onclick="document.getElementById('mobileFilterModal').classList.remove('show'); document.body.style.overflow='';"></button>
            </div>

            <div class="mobile-filter-body">

                {{-- ფასი --}}
                <div class="filter-block">
                    <div class="filter-section-title {{ ($priceMin || $priceMax) ? 'has-selected' : '' }}">
                        <span>ფასი @if($priceMin || $priceMax)<span class="filter-selected-badge">✓</span>@endif</span>
                    </div>
                    <div class="filter-body">
                        <div class="d-flex gap-2 mb-2">
                            <input type="number" class="form-control form-control-sm"
                                   wire:model.live.debounce.500ms="priceMin" placeholder="მინ. ₾" min="0">
                            <input type="number" class="form-control form-control-sm"
                                   wire:model.live.debounce.500ms="priceMax" placeholder="მაქს. ₾" min="0">
                        </div>
                        @if($priceMin || $priceMax)
                            <button wire:click="clearPriceFilter" class="btn btn-sm btn-outline-secondary w-100" style="font-size:12px;">გასუფთავება</button>
                        @endif
                    </div>
                </div>

                {{-- კატეგორიები --}}
                <div class="filter-block">
                    <div class="filter-section-title">
                        <span>{{ $currentCategory?->translation('ka')?->title ?? 'კატეგორიები' }}</span>
                    </div>
                    <div class="filter-body">
                        @if(!$selectedParent)
                            <ul class="list-unstyled m-0">
                                @foreach($parentCategories as $category)
                                    <li class="py-1">
                                        <a class="text-body text-decoration-none" style="font-size:13px;"
                                           href="#" wire:click.prevent="selectParent({{ $category->id }})">
                                            {{ $category->translation('ka')?->title ?? '—' }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <ul class="list-unstyled m-0">
                                @foreach($subCategories as $subCategory)
                                    <li class="py-1">
                                        <a class="text-decoration-none {{ $currentCategory?->id === $subCategory->id ? 'text-primary fw-semibold' : 'text-body' }}"
                                           style="font-size:13px;" href="#"
                                           wire:click.prevent="selectChild({{ $subCategory->id }})">
                                            {{ $subCategory->translation('ka')?->title ?? '—' }}
                                        </a>
                                    </li>
                                @endforeach
                                <li class="pt-2">
                                    <a href="#" wire:click.prevent="resetCategories" class="text-primary" style="font-size:12px;">← უკან</a>
                                </li>
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- ბრენდი --}}
                @if($brands->count() > 0)
                    <div class="filter-block">
                        <div class="filter-section-title {{ !empty($selectedBrands) ? 'has-selected' : '' }}">
                            <span>ბრენდი @if(!empty($selectedBrands))<span class="filter-selected-badge">{{ count($selectedBrands) }}</span>@endif</span>
                        </div>
                        <div class="filter-body">
                            @foreach($brands as $index => $brand)
                                <div class="form-check" @if($index >= 5 && !$this->showAllBrands) style="display:none;" @endif>
                                    <input type="checkbox" class="form-check-input"
                                           wire:model.live="selectedBrands"
                                           value="{{ $brand->id }}"
                                           id="m_brand_{{ $brand->id }}">
                                    <label class="form-check-label" for="m_brand_{{ $brand->id }}">
                                        {{ $brand->translation('ka')?->title ?? ('ბრენდი #' . $brand->id) }}
                                    </label>
                                </div>
                            @endforeach
                            @if($brands->count() > 5)
                                <button class="filter-show-more mt-2" type="button" wire:click="toggleShowAllBrands">
                                    @if($this->showAllBrands) ნაკლები ↑ @else მეტის ნახვა ({{ $brands->count() - 5 }}) ↓ @endif
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- სპეციფიკაციები --}}
                @if(!empty($specificationSections) && $specificationSections->count() > 0)
                    @foreach($specificationSections as $specName => $values)
                        @php
                            $selectedInSection = collect($selectedSpecs)->filter(fn($s) => str_starts_with($s, $specName . '::'));
                            $hasSelected       = $selectedInSection->count() > 0;
                            $valuesCount       = count($values);
                        @endphp
                        <div class="filter-block">
                            <div class="filter-section-title {{ $hasSelected ? 'has-selected' : '' }}">
                                <span>{{ $specName }} @if($hasSelected)<span class="filter-selected-badge">{{ $selectedInSection->count() }}</span>@endif</span>
                            </div>
                            <div class="filter-body" x-data="{ open: {{ $hasSelected ? 'true' : 'false' }} }">
                                @foreach($values as $i => $item)
                                    @php $isSelected = $selectedInSection->contains($specName . '::' . $item->value); @endphp
                                    <div class="form-check"
                                         @if($i >= 5) x-show="open || {{ $isSelected ? 'true' : 'false' }}" x-cloak @endif>
                                        <input type="checkbox" class="form-check-input"
                                               wire:model.live="selectedSpecs"
                                               value="{{ $specName }}::{{ $item->value }}"
                                               id="m_spec_{{ md5($specName . $item->value) }}">
                                        <label class="form-check-label" for="m_spec_{{ md5($specName . $item->value) }}">
                                            {{ $item->value }}
                                        </label>
                                    </div>
                                @endforeach
                                @if($valuesCount > 5)
                                    <button class="filter-show-more mt-2" type="button" @click="open = !open">
                                        <span x-show="!open">მეტის ნახვა ({{ $valuesCount - 5 }}) ↓</span>
                                        <span x-show="open" x-cloak>ნაკლები ↑</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- მხოლოდ ფასდაკლებული --}}
                <div class="filter-block pt-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input"
                               wire:model.live="onlyDiscounted"
                               id="m_discountFilter">
                        <label class="form-check-label fw-medium" style="font-size:13px;"
                               for="m_discountFilter">მხოლოდ ფასდაკლებული</label>
                    </div>
                </div>
            </div>

            {{-- ქვედა ღილაკები --}}
            <div class="mobile-filter-footer">
                @if($selectedBrands || $priceMin || $priceMax || $selectedSpecs || $onlyDiscounted)
                    <button wire:click="resetAllFilters" class="btn btn-outline-secondary flex-fill">გასუფთავება</button>
                @endif
                <button type="button" class="btn btn-primary flex-fill font-neue"
                        onclick="document.getElementById('mobileFilterModal').classList.remove('show'); document.body.style.overflow='';">
                    ნახვა ({{ $this->products->total() }})
                </button>
            </div>
        </div>

    </main>
</div>

@section('fb_pixel')
    <script>
        fbq('track', 'PageView', {}, { eventID: '{{ $eventId }}' });
    </script>
    <noscript><img height="1" width="1" style="display:none"
                   src="https://www.facebook.com/tr?id=1280014533998229&ev=PageView&noscript=1"
        /></noscript>
@endsection

@section('page_scripts')
    <script src="{{ asset('web-assets/vendor/nouislider/nouislider.min.js') }}"></script>
    @include('livewire.web.product.swiper-init')
@endsection