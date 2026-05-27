@section('seo')
    <title>
        {{ ($currentCategory?->translation('ka')->title ? $currentCategory->translation('ka')->title . ' — შეიძინე იაფად | iapi.ge' : 'პროდუქციის ჩამონათვალი — შეიძინე იაფად | iapi.ge') }}
    </title>
    <meta name="keywords" content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

@section('page_css')
    <link rel="stylesheet" href="{{ asset('web-assets/vendor/nouislider/nouislider.min.css') }}">
    <style>
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
        .filter-body .form-check {
            padding: 3px 0 3px 1.5em;
            margin: 0;
        }
        .filter-body .form-check-label {
            font-size: 13px;
            cursor: pointer;
            color: #444;
        }
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
        .filter-mobile-btn {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 999;
            border-radius: 50px;
            padding: 10px 24px;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        @media (min-width: 992px) {
            .filter-mobile-btn { display: none !important; }
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

                {{-- ✅ Desktop sidebar --}}
                <aside class="col-lg-3 d-none d-lg-block">
                    @include('livewire.web.product.partials.filter-content')
                </aside>

                <div class="col-12 col-lg-9">
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
    </main>

    {{-- ✅ მობილური ფილტრის ღილაკი --}}
    <button class="btn btn-dark filter-mobile-btn d-lg-none"
            type="button"
            data-bs-toggle="modal"
            data-bs-target="#mobileFilterModal">
        <i class="ci-filter me-2"></i>
        ფილტრი
        @php
            $activeFiltersCount = count($selectedSpecs) + count($selectedBrands ?? []) + ($priceMin ? 1 : 0) + ($priceMax ? 1 : 0) + ($onlyDiscounted ? 1 : 0);
        @endphp
        @if($activeFiltersCount > 0)
            <span class="badge bg-primary ms-1">{{ $activeFiltersCount }}</span>
        @endif
    </button>

    {{-- ✅ მობილური ფილტრის მოდალი --}}
    <div class="modal fade" id="mobileFilterModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-scrollable" style="margin: 0; max-width: 100%; height: 100%;">
            <div class="modal-content" style="height: 100%; border-radius: 0;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.08);">
                    <h5 class="modal-title font-neue" style="font-size: 16px;">ფილტრი</h5>
                    <div class="d-flex align-items-center gap-2">
                        @if($activeFiltersCount > 0)
                            <button wire:click="resetAllFilters"
                                    class="btn btn-sm btn-outline-secondary"
                                    style="font-size: 12px;"
                                    data-bs-dismiss="modal">
                                გასუფთავება
                            </button>
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body" style="padding: 12px 16px; overflow-y: auto;">
                    @include('livewire.web.product.partials.filter-content')
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.08);">
                    <button type="button"
                            class="btn btn-primary w-100 font-neue"
                            data-bs-dismiss="modal">
                        შედეგების ნახვა ({{ $this->products->total() }})
                    </button>
                </div>
            </div>
        </div>
    </div>
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
@endsection