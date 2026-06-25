@section('seo')
    <title>{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }} - IAPI.GE</title>
    <meta name="keywords" content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

@section('og_tags')
    <meta property="og:url" content="{{ route('web.products.view', $product->translations->where('locale', app()->getLocale())->first()->slug ?? $product->translations->where('locale', 'ka')->first()->slug) }}"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title" content="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}"/>
    <meta property="og:image" content="{{ asset('storage/'.$product->main_image) }}"/>
@endsection

@section('page_css')
    <style>
        /* ===== Product View ===== */
        .pv-breadcrumb { font-size: 13px; color: #888; }
        .pv-breadcrumb a { color: #888; text-decoration: none; }
        .pv-breadcrumb a:hover { color: #ff6900; }

        .pv-wrap { padding: 24px 0 60px; }

        /* Gallery */
        .pv-gallery { position: sticky; top: 20px; }
        .pv-main-img-wrap {
            background: #fafafa;
            border-radius: 16px;
            border: 1px solid #f0f1f3;
            overflow: hidden;
            aspect-ratio: 1/1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            margin-bottom: 12px;
        }
        .pv-main-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            transition: transform .3s ease;
        }
        .pv-main-img-wrap:hover .pv-main-img { transform: scale(1.05); }
        .pv-thumbs { display: flex; gap: 8px; flex-wrap: wrap; }
        .pv-thumb {
            width: 68px;
            height: 68px;
            border-radius: 10px;
            border: 2px solid #f0f1f3;
            background: #fafafa;
            overflow: hidden;
            padding: 4px;
            cursor: pointer;
            transition: border-color .15s ease;
        }
        .pv-thumb:hover, .pv-thumb.active { border-color: #ff6900; }
        .pv-thumb img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }

        /* Info Panel */
        .pv-info { padding-left: 32px; }
        @media (max-width: 991px) { .pv-info { padding-left: 0; margin-top: 24px; } }

        .pv-title {
            font-size: 20px;
            font-weight: 700;
            line-height: 1.3;
            color: #1a1a1a;
            margin-bottom: 12px;
        }
        @media (max-width: 575px) { .pv-title { font-size: 17px; } }

        .pv-meta { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
        .pv-sku { font-size: 12px; color: #aaa; }
        .pv-stock-yes { font-size: 12px; font-weight: 600; color: #22c55e; display: flex; align-items: center; gap: 4px; }
        .pv-stock-no  { font-size: 12px; font-weight: 600; color: #ef4444; display: flex; align-items: center; gap: 4px; }

        /* Price */
        .pv-price-wrap { margin-bottom: 20px; }
        .pv-price-now { font-size: 2rem; font-weight: 800; color: #ff6900; line-height: 1; }
        .pv-price-old { font-size: 15px; color: #bbb; text-decoration: line-through; margin-left: 8px; }
        .pv-discount-badge {
            display: inline-block;
            background: #fef2f2;
            color: #ef4444;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            margin-left: 10px;
        }
        .pv-installment {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            margin-top: 8px;
        }

        /* Warning */
        .pv-warning {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 500;
            color: #92400e;
        }

        /* Wishlist btn */
        .pv-wishlist-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #f9fafb;
            border: 1.5px solid #f0f1f3;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s ease;
            flex-shrink: 0;
        }
        .pv-wishlist-btn:hover { border-color: #ff6900; background: #fff5f0; }
        .pv-wishlist-btn i { font-size: 18px; color: #aaa; }
        .pv-wishlist-btn i.text-danger { color: #ef4444 !important; }

        /* Divider */
        .pv-divider { height: 1px; background: #f0f1f3; margin: 20px 0; }

        /* Purchase section */
        .pv-purchase { background: #fafafa; border: 1.5px solid #f0f1f3; border-radius: 16px; padding: 20px; }

        /* Description */
        .pv-desc {
            font-size: 13px;
            line-height: 1.8;
            color: #444;
            background: #fafafa;
            border: 1px solid #f0f1f3;
            border-radius: 12px;
            padding: 20px 24px;
            margin-top: 32px;
        }

        /* Specs */
        .pv-specs-wrap { margin-top: 32px; }
        .pv-specs-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff6900;
            display: inline-block;
        }
        .pv-specs-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            background: #fff;
            border: 1.5px solid #e5e7eb;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            cursor: pointer;
            transition: all .15s ease;
            margin-top: 16px;
        }
        .pv-specs-toggle:hover { border-color: #ff6900; color: #ff6900; }
    </style>
@endsection

<main class="content-wrapper">

    {{-- Breadcrumb --}}
    <div class="container pt-3 pb-2">
        @include('livewire.web.product.breadcrumb')
    </div>

    <div class="container pv-wrap">
        <div class="row g-0">

            {{-- Gallery --}}
            <div class="col-lg-5">
                <div class="pv-gallery">
                    @include('livewire.web.product.gallery')
                </div>
            </div>

            {{-- Info --}}
            <div class="col-lg-7">
                <div class="pv-info">

                    {{-- Title + Wishlist --}}
                    <div class="d-flex align-items-start gap-3 mb-2">
                        <h1 class="pv-title flex-grow-1">
                            {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
                        </h1>

                        {{-- Wishlist Alpine --}}
                        <div x-data="{
                            wishlistLoading: false,
                            inWishlist: false,
                            init() {
                                @auth
                                fetch('/wishlist/check', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                                    body: JSON.stringify({ ids: [{{ $product->id }}] }),
                                }).then(r => r.json()).then(ids => { this.inWishlist = ids.includes({{ $product->id }}); }).catch(() => {});
                                @endauth
                            },
                            toggle() {
                                if (this.wishlistLoading) return;
                                this.wishlistLoading = true;
                                fetch('/wishlist/toggle', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                                    body: JSON.stringify({ product_id: {{ $product->id }} }),
                                }).then(r => r.json()).then(data => {
                                    if (data.auth === false) { window.location.href = data.redirect; return; }
                                    if (data.success) {
                                        this.inWishlist = data.in_wishlist;
                                        window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                                        if (window.Livewire) window.Livewire.dispatch('wishlistUpdated');
                                    }
                                }).finally(() => { this.wishlistLoading = false; });
                            }
                        }">
                            <button type="button" @click="toggle()" :disabled="wishlistLoading" class="pv-wishlist-btn">
                                <template x-if="!wishlistLoading">
                                    <i :class="inWishlist ? 'ci-heart-filled text-danger' : 'ci-heart'"></i>
                                </template>
                                <template x-if="wishlistLoading">
                                    <span class="spinner-border spinner-border-sm"></span>
                                </template>
                            </button>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="pv-meta">
                        <span class="pv-sku">SKU: {{ $product->id }}</span>
                        @if(auth()->check() && auth()->user()->role_id === 2)
                            <span style="font-size: 12px; color: #ff6900; background: #fff3ec; border: 1px solid #ffd5b8; border-radius: 6px; padding: 2px 8px;">
                                {{ $product->sku }}
                            </span>
                        @endif
                        @if($product->show === 1)
                            <span class="pv-stock-yes"><i class="ci-check-circle"></i> მარაგშია</span>
                        @else
                            <span class="pv-stock-no"><i class="ci-close-circle"></i> ამოწურულია</span>
                        @endif
                    </div>

                    {{-- Warning --}}
                    @if($product->category_id === 183 || $product->category_id === 206)
                        <div class="pv-warning">
                            <i class="ci-info" style="font-size: 18px; flex-shrink: 0;"></i>
                            ეს პროდუქტი საჭიროებს ნაშთის გადამოწმებას. გთხოვთ დაგვიკავშირდეთ შეძენამდე.
                        </div>
                    @endif

                    {{-- Price --}}
                    <div class="pv-price-wrap">
                        <div class="d-flex align-items-baseline flex-wrap gap-1">
                            @if(!empty($product->price->discount_price))
                                <span class="pv-price-now">{{ number_format($product->price->discount_price, 2) }} ₾</span>
                                <span class="pv-price-old">{{ number_format($product->price->regular_price, 2) }} ₾</span>
                                @if($product->price->discount_percent)
                                    <span class="pv-discount-badge">-{{ $product->price->discount_percent }}%</span>
                                @endif
                            @else
                                <span class="pv-price-now">{{ number_format($product->price->regular_price, 2) }} ₾</span>
                            @endif
                        </div>
                        @php
                            $priceForInstallment = $product->price->discount_price ?: $product->price->regular_price;
                        @endphp
                        @if($priceForInstallment > 100)
                            <div class="pv-installment">
                                <i class="ci-credit-card"></i>
                                თვეში {{ number_format($priceForInstallment / 24) }} ₾-დან
                            </div>
                        @endif
                    </div>

                    {{-- Short Specs --}}
                    @include('livewire.web.product.specs')

                    <div class="pv-divider"></div>

                    {{-- Purchase Section --}}
                    <div class="pv-purchase">
                        @include('livewire.web.product.purchase-section')
                    </div>

                    {{-- Variations --}}
                    @foreach($product->variations as $variation)
                        <div class="mt-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <small class="text-muted">{{ $variation->name }}:</small>
                                <span class="fw-semibold" style="font-size: 13px;">{{ $variation->value }}</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($variation->items as $item)
                                    @if(!empty($item->product) && $item->product->show === 1)
                                        @php $slug = \App\Models\Product\ProductTranslation::where('product_id', $item->product->id)->where('locale', 'ka')->first(); @endphp
                                        @if($item->is_color === 0)
                                            <a href="{{ route('web.products.view', $slug->slug) }}"
                                               class="btn btn-sm btn-outline-secondary @if($item->supplier_product_id === $product->supplier_product_id) active @endif"
                                               style="font-size: 12px; border-radius: 8px;">
                                                {{ $item->value }}
                                            </a>
                                        @else
                                            <a href="{{ route('web.products.view', $slug->slug) }}"
                                               class="btn btn-color fs-xl @if($item->supplier_product_id === $product->supplier_product_id) active @endif"
                                               style="color: {{ $item->value }}"></a>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                </div>
            </div>
        </div>

        {{-- Description --}}
        @if(!empty($product->translation('ka')->description))
            <div class="pv-desc">
                {!! $product->translation('ka')->description !!}
            </div>
        @endif

        {{-- Full Specifications --}}
        @if(in_array($product->supplier_id, [2, 4, 8, 11, 12]) && $product->fullSpecifications?->count())
            <div class="pv-specs-wrap">
                <div class="pv-specs-title">სრული მახასიათებლები</div>
                <div id="specification-section" class="collapsed"
                     style="overflow: hidden; max-height: 300px; transition: max-height .4s ease;">
                    <div class="@if(count($product->fullSpecifications) > 1) masonry-grid @endif">
                        @foreach($product->fullSpecifications as $spec_section)
                            <div class="masonry-item mb-4">
                                <h3 class="h6 fw-bold mb-3" style="color: #1a1a1a;">{{ $spec_section->name }}</h3>
                                <ul class="list-unstyled d-flex flex-column gap-2 fs-sm m-0">
                                    @foreach($spec_section->list as $item)
                                        <li class="d-flex align-items-center position-relative pe-4"
                                            style="padding: 6px 0; border-bottom: 1px solid #f5f5f5;">
                                            <span style="color: #888; min-width: 140px; flex-shrink: 0; font-size: 12px;">{{ $item->name }}</span>
                                            <span class="fw-medium" style="color: #1a1a1a; font-size: 13px;">{{ $item->value }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex justify-content-center">
                    <button type="button" class="pv-specs-toggle" id="specs-toggle-btn">
                        <i class="ci-chevron-down" id="specs-toggle-icon"></i>
                        სრული მახასიათებლები
                    </button>
                </div>
            </div>
        @endif

        {{-- Similar Products --}}
        <div class="mt-5">
            @include('livewire.web.product.similar-products')
        </div>

        @include('livewire.web.partials.installment-modal')
    </div>

</main>

<style>
    .masonry-grid { column-count: 2; }
    .masonry-item { display: inline-block; width: 100%; break-inside: avoid; }
    @media (max-width: 767px) { .masonry-grid { column-count: 1; } }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const section = document.getElementById("specification-section");
        const btn     = document.getElementById("specs-toggle-btn");
        const icon    = document.getElementById("specs-toggle-icon");
        if (!btn || !section) return;

        let expanded = false;
        btn.addEventListener("click", function () {
            expanded = !expanded;
            section.style.maxHeight = expanded ? section.scrollHeight + 'px' : '300px';
            icon.className = expanded ? 'ci-chevron-up' : 'ci-chevron-down';
            btn.querySelector('span') && (btn.querySelector('span').textContent = expanded ? 'დამალვა' : 'სრული მახასიათებლები');
        });
    });
</script>

<script src="https://webstatic.bog.ge/bog-sdk/bog-sdk.js?version=2&client_id=57315"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('bog:installment', (installment_data) => {
            BOG.Calculator.open({
                bnpl: false, amount: installment_data.amount, onClose: () => {},
                onRequest: (selected, successCb, closeCb) => {
                    const { amount, month, discount_code } = selected;
                    axios.post(installment_data.url, { amount, month, discount_code })
                        .then(r => successCb(r.data.orderId)).catch(() => closeCb());
                    return false;
                }, onComplete: () => false
            });
        });
        Livewire.on('bog:installment-part', (part_installment_data) => {
            BOG.Calculator.open({
                bnpl: true, amount: part_installment_data.amount, onClose: () => {},
                onRequest: (selected, successCb, closeCb) => {
                    const { amount, month, discount_code } = selected;
                    axios.post(part_installment_data.url, { amount, month, discount_code })
                        .then(r => successCb(r.data.orderId)).catch(() => closeCb());
                    return false;
                }, onComplete: () => false
            });
        });
    });
</script>

@section('fb_pixel')
    @if($event_id)
        @php
            $actualPrice = ($product->price->discount_price && $product->price->discount_price > 0)
                ? $product->price->discount_price
                : $product->price->regular_price;
        @endphp
        <script>
            fbq('track', 'ViewContent', {
                content_ids: ['{{ $product->id }}'],
                content_type: 'product',
                content_name: '{{ addslashes($product->translation('ka')->title) }}',
                value: {{ $actualPrice }},
                currency: 'GEL',
                contents: [{ id: '{{ $product->id }}', quantity: 1 }]
            }, { eventID: '{{ $event_id }}' });
        </script>
    @endif
    <script>fbq('track', 'PageView', {}, { eventID: '{{ $eventId2 }}' });</script>
    <noscript><img height="1" width="1" style="display:none"
                   src="https://www.facebook.com/tr?id=1280014533998229&ev=PageView&noscript=1"/></noscript>
@endsection