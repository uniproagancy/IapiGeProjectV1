@php
    $catTitle = $currentCategory?->translation('ka')?->title;
    $seoTitle = $catTitle
        ? $catTitle . ' — იყიდე საუკეთესო ფასად | IAPI.GE'
        : 'პროდუქციის კატალოგი — ტექნიკა და ელექტრონიკა | IAPI.GE';
    $seoDesc = $catTitle
        ? $catTitle . ' — ფართო არჩევანი საუკეთესო ფასად IAPI.GE-ზე. სწრაფი მიტანა და გარანტია მთელ საქართველოში.'
        : 'იყიდე ტექნიკა და ელექტრონიკა IAPI.GE-ზე. ტელეფონები, კომპიუტერები, სახლის ტექნიკა საუკეთესო ფასად საქართველოში.';
@endphp

@section('seo')
    <title>{{ $seoTitle }}</title>
    <meta name="keywords" content="{{ $catTitle ?? 'ტექნიკა' }}, iapi.ge, ონლაინ მაღაზია, ელექტრონიკა, საუკეთესო ფასი, საქართველო">
@endsection

@section('meta_description'){{ $seoDesc }}@endsection

@section('canonical'){{ $currentCategory ? route('web.products.index', $currentCategory->translation('ka')?->slug) : route('web.products.index') }}@endsection

@section('og_tags')
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $currentCategory ? route('web.products.index', $currentCategory->translation('ka')?->slug) : route('web.products.index') }}">
    <meta property="og:image" content="{{ asset('web-assets/img/logo.png') }}">
@endsection

@section('structured_data')
    @php
        $breadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'მთავარი', 'item' => route('web.main.index')],
        ];
        if ($currentCategory) {
            $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $catTitle, 'item' => route('web.products.index', $currentCategory->translation('ka')?->slug)];
        } else {
            $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 2, 'name' => 'კატალოგი'];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbItems], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('page_css')
    <link rel="stylesheet" href="{{ asset('web-assets/vendor/nouislider/nouislider.min.css') }}">
    <style>
        [x-cloak] { display: none !important; }

        /* ===== Sidebar ===== */
        .filter-sidebar {
            position: sticky;
            top: 80px;
        }
        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f1f3;
            margin-bottom: 12px;
        }
        .filter-header-title {
            font-size: 15px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .filter-clear-all {
            font-size: 12px;
            color: #ff6900;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-weight: 500;
            text-decoration: none;
        }
        .filter-clear-all:hover { text-decoration: underline; }

        .filter-block { margin-bottom: 2px; }
        .filter-section-title {
            border-bottom: 1px solid #f0f1f3;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            user-select: none;
            color: #1a1a1a;
            transition: color .15s ease;
        }
        .filter-section-title:hover { color: #ff6900; }
        .filter-section-title.has-selected { color: #ff6900; }
        .filter-section-title i { font-size: 11px; color: #bbb; transition: transform .2s; }
        .filter-section-title[aria-expanded="false"] i { transform: rotate(-90deg); }

        .filter-body { padding: 8px 0 6px 0; }
        .filter-body .form-check { padding: 3px 0 3px 1.6em; margin: 0; }
        .filter-body .form-check-label {
            font-size: 13px;
            cursor: pointer;
            color: #444;
            transition: color .15s ease;
        }
        .filter-body .form-check-label:hover { color: #ff6900; }
        .filter-body .form-check-input { margin-top: 3px; border-color: #ddd; }
        .filter-body .form-check-input:checked {
            background-color: #ff6900;
            border-color: #ff6900;
        }

        .filter-selected-badge {
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 10px;
            background: #ff6900;
            color: #fff;
            margin-left: 6px;
            font-weight: 700;
        }
        .filter-show-more {
            font-size: 12px;
            color: #ff6900;
            background: none;
            border: none;
            padding: 3px 0;
            cursor: pointer;
            font-weight: 500;
        }
        .filter-show-more:hover { text-decoration: underline; }

        /* ===== Price Slider ===== */
        .price-inputs {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }
        .price-input {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 5px 8px;
            font-size: 13px;
            color: #333;
            outline: none;
            width: 100%;
        }
        .price-input:focus { border-color: #ff6900; }
        .price-sep { color: #bbb; font-size: 13px; flex-shrink: 0; }

        /* ===== noUiSlider custom style ===== */
        .noUi-target {
            background: #f0f0f0;
            border: none;
            box-shadow: none;
            height: 4px;
            border-radius: 4px;
        }
        .noUi-connect { background: #ff6900; }
        .noUi-handle {
            width: 18px !important;
            height: 18px !important;
            top: -7px !important;
            right: -9px !important;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #ff6900;
            box-shadow: 0 2px 6px rgba(255,105,0,0.25);
            cursor: pointer;
        }
        .noUi-handle:before, .noUi-handle:after { display: none; }
        .noUi-tooltip {
            background: #ff6900;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            padding: 2px 6px;
        }

        /* ===== Category Tree ===== */
        .cat-tree-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
            color: #444;
            cursor: pointer;
            text-decoration: none;
            transition: color .15s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .cat-tree-item:hover { color: #ff6900; }
        .cat-tree-item.active { color: #ff6900; font-weight: 600; }
        .cat-tree-back {
            font-size: 12px;
            color: #888;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 0 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .cat-tree-back:hover { color: #ff6900; }

        /* ===== Load More ===== */
        .load-more-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 40px;
            background: #fff;
            border: 2px solid #ff6900;
            color: #ff6900;
            font-size: 14px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all .2s ease;
            min-width: 200px;
        }
        .load-more-btn:hover { background: #ff6900; color: #fff; }
        .load-more-btn:disabled { opacity: .6; cursor: not-allowed; }

        /* ===== Mobile Filter ===== */
        .mobile-filter-modal {
            position: fixed; inset: 0;
            background: #fff; z-index: 1050;
            transform: translateX(100%);
            transition: transform .3s ease;
            display: flex; flex-direction: column;
        }
        .mobile-filter-modal.show { transform: translateX(0); }
        .mobile-filter-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            flex-shrink: 0;
        }
        .mobile-filter-body { flex: 1; overflow-y: auto; padding: 12px 16px; }
        .mobile-filter-footer {
            display: flex; gap: 10px; padding: 12px 16px;
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
                @if($currentCategory)
                    <li class="breadcrumb-item active" aria-current="page">{{ $catTitle }}</li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">კატალოგი</li>
                @endif
            </ol>
        </nav>

        <h1 class="h3 container mb-3 font-neue">
            {{ $currentCategory?->translation('ka')?->title ?? 'პროდუქციის ჩამონათვალი' }}
        </h1>

        <section class="container mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="h6 fs-sm fw-normal text-nowrap mb-0 font-neue">
                    ნაპოვნია <span class="fw-semibold">{{ $this->products->total() }}</span> პროდუქტი
                </div>
                <div class="d-flex align-items-center gap-2">
                    {{-- მობილური ფილტრის ღილაკი --}}
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary d-lg-none"
                            @click="$dispatch('open-mobile-filter')">
                        <i class="ci-filter me-1"></i> ფილტრი
                        @php $activeCount = (int)(!empty($priceMin) || !empty($priceMax)) + count((array)$selectedBrands) + count((array)$selectedSpecs) + (int)$onlyDiscounted; @endphp
                        @if($activeCount > 0)
                            <span class="badge bg-danger ms-1">{{ $activeCount }}</span>
                        @endif
                    </button>
                    <label class="fs-sm text-muted mb-0 text-nowrap d-none d-sm-inline">დალაგება:</label>
                    <select class="form-select form-select-sm" style="min-width: 190px;" wire:model.live="sort">
                        <option value="newest">უახლესი</option>
                        <option value="price_asc">ფასი: დაბლიდან მაღლა</option>
                        <option value="price_desc">ფასი: მაღლიდან დაბლა</option>
                        <option value="oldest">ძველი</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="container pb-5 mb-sm-2 mb-md-3 mb-lg-4 mb-xl-5">
            <div class="row">

                {{-- ===== Desktop Sidebar ===== --}}
                <aside class="col-lg-2 d-none d-lg-block">
                    <div class="filter-sidebar">

                        {{-- Header --}}
                        <div class="filter-header">
                            <span class="filter-header-title">ფილტრი</span>
                            @if($selectedBrands || $priceMin || $priceMax || $selectedSpecs || $onlyDiscounted)
                                <button class="filter-clear-all" wire:click="resetAllFilters">გასუფთავება</button>
                            @endif
                        </div>

                        {{-- კატეგორიები --}}
                        <div class="filter-block">
                            <div class="filter-section-title"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#filter_categories"
                                 aria-expanded="true">
                                <span>{{ $currentCategory?->translation('ka')?->title ?? 'კატეგორიები' }}</span>
                                <i class="ci-chevron-down fs-sm"></i>
                            </div>
                            <div class="collapse show" id="filter_categories">
                                <div class="filter-body">
                                    @if(!$selectedParent)
                                        @foreach($parentCategories as $category)
                                            <button class="cat-tree-item" wire:click="selectParent({{ $category->id }})">
                                                <span>{{ $category->translation('ka')?->title ?? '—' }}</span>
                                                <i class="ci-chevron-right" style="font-size:10px; color:#ccc;"></i>
                                            </button>
                                        @endforeach
                                    @else
                                        <button class="cat-tree-back" wire:click="resetCategories">
                                            ← უკან
                                        </button>
                                        @foreach($subCategories as $subCategory)
                                            <button class="cat-tree-item {{ $currentCategory?->id === $subCategory->id ? 'active' : '' }}"
                                                    wire:click="selectChild({{ $subCategory->id }})">
                                                <span>{{ $subCategory->translation('ka')?->title ?? '—' }}</span>
                                                @if($currentCategory?->id === $subCategory->id)
                                                    <i class="ci-check" style="font-size:11px; color:#ff6900;"></i>
                                                @endif
                                            </button>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ფასი --}}
                        <div class="filter-block">
                            <div class="filter-section-title {{ ($priceMin || $priceMax) ? 'has-selected' : '' }}"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#filter_price"
                                 aria-expanded="true">
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
                                    {{-- Inputs --}}
                                    <div class="price-inputs mb-2">
                                        <input type="number" class="price-input"
                                               wire:model.live.debounce.600ms="priceMin"
                                               placeholder="{{ $this->priceRange['min'] }} ₾"
                                               min="{{ $this->priceRange['min'] }}"
                                               max="{{ $this->priceRange['max'] }}"
                                               id="price-min-input">
                                        <span class="price-sep">—</span>
                                        <input type="number" class="price-input"
                                               wire:model.live.debounce.600ms="priceMax"
                                               placeholder="{{ $this->priceRange['max'] }} ₾"
                                               min="{{ $this->priceRange['min'] }}"
                                               max="{{ $this->priceRange['max'] }}"
                                               id="price-max-input">
                                    </div>
                                    {{-- Slider --}}
                                    <div id="price-slider" wire:ignore
                                         data-min="{{ $this->priceRange['min'] }}"
                                         data-max="{{ $this->priceRange['max'] }}"
                                         data-current-min="{{ $priceMin ?: $this->priceRange['min'] }}"
                                         data-current-max="{{ $priceMax ?: $this->priceRange['max'] }}">
                                    </div>
                                    @if($priceMin || $priceMax)
                                        <button wire:click="clearPriceFilter"
                                                class="filter-show-more mt-2">გასუფთავება ✕</button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ბრენდი --}}
                        @if($brands->count() > 0)
                            <div class="filter-block">
                                <div class="filter-section-title {{ !empty($selectedBrands) ? 'has-selected' : '' }}"
                                     data-bs-toggle="collapse"
                                     data-bs-target="#filter_brands"
                                     aria-expanded="true">
                                    <span>
                                        ბრენდი
                                        @if(!empty($selectedBrands))
                                            <span class="filter-selected-badge">{{ count($selectedBrands) }}</span>
                                        @endif
                                    </span>
                                    <i class="ci-chevron-down fs-sm"></i>
                                </div>
                                <div class="collapse show" id="filter_brands">
                                    <div class="filter-body" x-data="{ open: {{ !empty($selectedBrands) ? 'true' : 'false' }} }">
                                        @foreach($brands as $index => $brand)
                                            <div class="form-check"
                                                 @if($index >= 6) x-show="open" x-cloak @endif>
                                                <input type="checkbox" class="form-check-input"
                                                       wire:model.live="selectedBrands"
                                                       value="{{ $brand->id }}"
                                                       id="brand_{{ $brand->id }}">
                                                <label class="form-check-label" for="brand_{{ $brand->id }}">
                                                    {{ $brand->translation('ka')?->title ?? ('ბრენდი #' . $brand->id) }}
                                                </label>
                                            </div>
                                        @endforeach
                                        @if($brands->count() > 6)
                                            <button class="filter-show-more mt-1" type="button" @click="open = !open">
                                                <span x-show="!open">მეტის ნახვა ({{ $brands->count() - 6 }}) ↓</span>
                                                <span x-show="open" x-cloak>ნაკლები ↑</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- სპეციფიკაციები --}}
                        @if(!empty($specificationSections) && $specificationSections->count() > 0)
                            @foreach($specificationSections as $specName => $values)
                                @php
                                    $selectedInSection = collect($selectedSpecs)->filter(fn($s) => str_starts_with($s, $specName . '::'));
                                    $hasSelected = $selectedInSection->count() > 0;
                                    $specKey     = 'spec_' . md5($specName);
                                    $valuesCount = count($values);
                                @endphp
                                <div class="filter-block">
                                    <div class="filter-section-title {{ $hasSelected ? 'has-selected' : '' }}"
                                         data-bs-toggle="collapse"
                                         data-bs-target="#{{ $specKey }}"
                                         aria-expanded="{{ $hasSelected ? 'true' : 'false' }}">
                                        <span>
                                            {{ $specName }}
                                            @if($hasSelected)
                                                <span class="filter-selected-badge">{{ $selectedInSection->count() }}</span>
                                            @endif
                                        </span>
                                        <i class="ci-chevron-down fs-sm"></i>
                                    </div>
                                    <div class="collapse {{ $hasSelected ? 'show' : '' }}" id="{{ $specKey }}">
                                        <div class="filter-body" x-data="{ open: {{ $hasSelected ? 'true' : 'false' }} }">
                                            @foreach($values as $i => $item)
                                                @php $isSelected = $selectedInSection->contains($specName . '::' . $item->value); @endphp
                                                <div class="form-check"
                                                     @if($i >= 5) x-show="open || {{ $isSelected ? 'true' : 'false' }}" x-cloak @endif>
                                                    <input type="checkbox" class="form-check-input"
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
                                                <button class="filter-show-more mt-1" type="button" @click="open = !open">
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
                                <input type="checkbox" class="form-check-input"
                                       wire:model.live="onlyDiscounted"
                                       id="discountFilter">
                                <label class="form-check-label fw-medium" style="font-size: 13px;"
                                       for="discountFilter">მხოლოდ ფასდაკლებული</label>
                            </div>
                        </div>

                    </div>
                </aside>

                {{-- ===== Products ===== --}}
                <div class="col-lg-10">
                    @if($currentCategory && isset($categorySections) && $categorySections->count() > 0)
                        @include('livewire.web.partials.sections-carousel2', ['sections' => $categorySections])
                        <span class="mb-1"></span>
                    @endif

                    @if($this->products->count() > 0)
                        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 pb-3 mb-3">
                            @foreach($this->products as $product)
                                <div class="col" wire:key="product-{{ $product->id }}">
                                    @include('livewire.web.product.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-center mt-2">
                            <button wire:click="loadMore" wire:loading.attr="disabled" class="load-more-btn">
                                <span wire:loading.remove>
                                    <i class="ci-refresh me-2"></i>მეტის ნახვა
                                </span>
                                <span wire:loading>
                                    <span class="spinner-border spinner-border-sm me-2"></span>იტვირთება...
                                </span>
                            </button>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <svg class="w-25 h-25 mx-auto text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
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

        {{-- ===== Mobile Filter Modal ===== --}}
        <div wire:ignore
             x-data="{ open: false }"
             @open-mobile-filter.window="open = true; document.body.style.overflow = 'hidden'"
             @close-mobile-filter.window="open = false; document.body.style.overflow = ''"
             id="mobileFilterModal"
             class="mobile-filter-modal d-lg-none"
             :class="{ 'show': open }">

            <div class="mobile-filter-header">
                <h5 class="m-0 font-neue">ფილტრი</h5>
                <button type="button" class="btn-close" @click="$dispatch('close-mobile-filter')"></button>
            </div>

            <div class="mobile-filter-body">

                {{-- ფასი --}}
                <div class="filter-block">
                    <div class="filter-section-title">
                        <span>ფასი</span>
                    </div>
                    <div class="filter-body">
                        <div class="price-inputs">
                            <input type="number" class="price-input"
                                   wire:model.live.debounce.500ms="priceMin"
                                   placeholder="მინ. ₾" min="0">
                            <span class="price-sep">—</span>
                            <input type="number" class="price-input"
                                   wire:model.live.debounce.500ms="priceMax"
                                   placeholder="მაქს. ₾" min="0">
                        </div>
                        @if($priceMin || $priceMax)
                            <button wire:click="clearPriceFilter" class="filter-show-more">გასუფთავება ✕</button>
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
                            @foreach($parentCategories as $category)
                                <button class="cat-tree-item" wire:click="selectParent({{ $category->id }})">
                                    <span>{{ $category->translation('ka')?->title ?? '—' }}</span>
                                    <i class="ci-chevron-right" style="font-size:10px; color:#ccc;"></i>
                                </button>
                            @endforeach
                        @else
                            <button class="cat-tree-back" wire:click="resetCategories">← უკან</button>
                            @foreach($subCategories as $subCategory)
                                <button class="cat-tree-item {{ $currentCategory?->id === $subCategory->id ? 'active' : '' }}"
                                        wire:click="selectChild({{ $subCategory->id }})">
                                    <span>{{ $subCategory->translation('ka')?->title ?? '—' }}</span>
                                </button>
                            @endforeach
                        @endif
                    </div>
                </div>

                {{-- ბრენდი --}}
                @if($brands->count() > 0)
                    <div class="filter-block">
                        <div class="filter-section-title {{ !empty($selectedBrands) ? 'has-selected' : '' }}">
                            <span>
                                ბრენდი
                                @if(!empty($selectedBrands))
                                    <span class="filter-selected-badge">{{ count($selectedBrands) }}</span>
                                @endif
                            </span>
                        </div>
                        <div class="filter-body">
                            @foreach($brands as $brand)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input"
                                           wire:model.live="selectedBrands"
                                           value="{{ $brand->id }}"
                                           id="m_brand_{{ $brand->id }}">
                                    <label class="form-check-label" for="m_brand_{{ $brand->id }}">
                                        {{ $brand->translation('ka')?->title ?? ('ბრენდი #' . $brand->id) }}
                                    </label>
                                </div>
                            @endforeach
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
                                <span>
                                    {{ $specName }}
                                    @if($hasSelected)
                                        <span class="filter-selected-badge">{{ $selectedInSection->count() }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="filter-body" x-data="{ open: false }">
                                @foreach($values as $i => $item)
                                    @php $isSelected = $selectedInSection->contains($specName . '::' . $item->value); @endphp
                                    <div class="form-check"
                                         @if($i >= 5) x-show="open || {{ $isSelected ? 'true' : 'false' }}" x-cloak @endif>
                                        <input type="checkbox" class="form-check-input"
                                               wire:model.live="selectedSpecs"
                                               value="{{ $specName }}::{{ $item->value }}"
                                               id="m_spec_{{ md5($specName . $item->value) }}">
                                        <label class="form-check-label"
                                               for="m_spec_{{ md5($specName . $item->value) }}">
                                            {{ $item->value }}
                                        </label>
                                    </div>
                                @endforeach
                                @if($valuesCount > 5)
                                    <button class="filter-show-more mt-1" type="button" @click="open = !open">
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
                               wire:model.live="onlyDiscounted" id="m_discountFilter">
                        <label class="form-check-label fw-medium" style="font-size:13px;"
                               for="m_discountFilter">მხოლოდ ფასდაკლებული</label>
                    </div>
                </div>

            </div>

            <div class="mobile-filter-footer">
                @if($selectedBrands || $priceMin || $priceMax || $selectedSpecs || $onlyDiscounted)
                    <button wire:click="resetAllFilters" class="btn btn-outline-secondary flex-fill">
                        გასუფთავება
                    </button>
                @endif
                <button type="button"
                        class="btn btn-primary flex-fill font-neue"
                        @click="$dispatch('close-mobile-filter')">
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
                   src="https://www.facebook.com/tr?id=1280014533998229&ev=PageView&noscript=1"/></noscript>
@endsection

@section('page_scripts')
    <script src="{{ asset('web-assets/vendor/nouislider/nouislider.min.js') }}"></script>

    <script>
        function initPriceSlider() {
            const slider = document.getElementById('price-slider');
            if (!slider || slider.noUiSlider) return;

            const rangeMin    = parseFloat(slider.dataset.min) || 0;
            const rangeMax    = parseFloat(slider.dataset.max) || 50000;
            const currentMin  = parseFloat(slider.dataset.currentMin) || rangeMin;
            const currentMax  = parseFloat(slider.dataset.currentMax) || rangeMax;

            const minInput = document.getElementById('price-min-input');
            const maxInput = document.getElementById('price-max-input');

            noUiSlider.create(slider, {
                start: [currentMin, currentMax],
                connect: true,
                range: { min: rangeMin, max: rangeMax },
                step: 1,
                tooltips: [
                    { to: v => Math.round(v) + ' ₾', from: v => Number(v) },
                    { to: v => Math.round(v) + ' ₾', from: v => Number(v) },
                ],
                format: {
                    to: v => Math.round(v),
                    from: v => Number(v),
                },
            });

            // Slider → inputs (live)
            slider.noUiSlider.on('update', function (values) {
                if (minInput) minInput.value = values[0];
                if (maxInput) maxInput.value = values[1];
            });

            // Slider → Livewire (მხოლოდ drag-ის შემდეგ)
            slider.noUiSlider.on('change', function (values) {
            @this.set('priceMin', values[0] > rangeMin ? values[0] : null);
            @this.set('priceMax', values[1] < rangeMax ? values[1] : null);
            });

            // Inputs → Slider
            if (minInput) {
                minInput.addEventListener('change', function () {
                    slider.noUiSlider.set([this.value, null]);
                });
            }
            if (maxInput) {
                maxInput.addEventListener('change', function () {
                    slider.noUiSlider.set([null, this.value]);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', initPriceSlider);

        // Livewire re-render-ზე slider-ი ხელახლა init-ი
        document.addEventListener('livewire:navigated', initPriceSlider);
        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                setTimeout(() => {
                    const slider = document.getElementById('price-slider');
                    if (slider && !slider.noUiSlider) {
                        initPriceSlider();
                    }
                }, 50);
            });
        });
    </script>

    @include('livewire.web.product.swiper-init')
@endsection