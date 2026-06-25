@section('seo')
    <title>{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }} - IAPI.GE</title>
    <meta name="keywords"
          content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

@section('og_tags')
    <meta property="og:url"
          content="{{ route('web.products.view', $product->translations->where('locale', app()->getLocale())->first()->slug ?? $product->translations->where('locale', 'ka')->first()->slug) }}"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title"
          content="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}"/>
    <meta property="og:image" content="{{ asset('storage/'.$product->main_image) }}"/>
@endsection

<main class="content-wrapper">
    @include('livewire.web.product.breadcrumb')
    <section class="container pb-5 mb-1 mb-sm-2 mb-md-3 mb-lg-4 mb-xl-5">
        <div class="row">
            <div class="col-md-10 col-xl-8 pt-1">
                <div class="row">
                    <div class="d-flex justify-content-between">
                        <h1 class="h3 mb-1 font-neue">
                            {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
                        </h1>
                        <div x-data="{
    wishlistLoading: false,
    inWishlist: false,
    init() {
        @auth
        fetch('/wishlist/check?ids={{ $product->id }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
            },
            body: JSON.stringify({ ids: [{{ $product->id }}] }),
        })
        .then(r => r.json())
        .then(ids => { this.inWishlist = ids.includes({{ $product->id }}); })
        .catch(() => {});
        @endauth
    },
    toggle() {
        if (this.wishlistLoading) return;
        this.wishlistLoading = true;
        fetch('/wishlist/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
            },
            body: JSON.stringify({ product_id: {{ $product->id }} }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.auth === false) { window.location.href = data.redirect; return; }
            if (data.success) {
                this.inWishlist = data.in_wishlist;
                window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                if (window.Livewire) window.Livewire.dispatch('wishlistUpdated');
            }
        })
        .finally(() => { this.wishlistLoading = false; });
    }
}">
                            <button type="button"
                                    @click="toggle()"
                                    :disabled="wishlistLoading"
                                    class="btn btn-secondary animate-pulse"
                                    :aria-label="inWishlist ? 'სურვილების სიიდან წაშლა' : 'სურვილების სიაში დამატება'">
                                <template x-if="!wishlistLoading">
                                    <i :class="inWishlist ? 'ci-heart-filled text-danger' : 'ci-heart'" class="fs-sm"></i>
                                </template>
                                <template x-if="wishlistLoading">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </template>
                            </button>
                        </div>
                    </div>

                    {{-- ⚠️ აუზი — კატეგორია 183 / 206 --}}
                    @if($product->category_id === 183 OR $product->category_id === 206)
                        <div class="col-12 mb-2">
                            <div class="d-flex align-items-center gap-2 rounded-3 px-3 py-2"
                                 style="background: #fff3cd; border: 1px solid #ffc107;">
                                <i class="ci-info" style="color: #856404; font-size: 18px; flex-shrink: 0;"></i>
                                <span style="color: #856404; font-size: 13px; font-weight: 500;">
                                    ⚠️ ეს პროდუქტი საჭიროებს ნაშთის გადამოწმებას. გთხოვთ დაგვიკავშირდეთ შეძენამდე.
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="col-md-8">
                        {{-- SKU --}}
                        <div class="d-flex align-items-center gap-3 mb-1">
                            <span class="font-neue" style="font-size: 14px; color: #888;">
                                SKU: {{ $product->id }}
                            </span>
                            {{-- მომწოდებლის SKU — მხოლოდ role_id=2 --}}
                            @if(auth()->check() && auth()->user()->role_id === 2)
                                <span class="font-neue" style="font-size: 13px; color: #ff6900; background: #fff3ec; border: 1px solid #ffd5b8; border-radius: 6px; padding: 2px 8px;">
                                    მომწოდებლის SKU: {{ $product->sku }}
                                </span>
                            @endif
                        </div>
                        @include('livewire.web.product.gallery')
                    </div>
                    <div class="col-md-4">
                        @include('livewire.web.product.specs')
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-xl-4 pt-1">
                @include('livewire.web.product.purchase-section')
            </div>
            @if(!empty($product->translation('ka')->description))
                <div class="col-12 mt-3">
                    <div class="row row-cols-1 row-cols-md-1">
                        <div class="col mb-3 mb-md-0">
                            <div class="pe-lg-2 pe-xl-3">
                                <div style="
                                font-size: 13px;
                                border: 1px solid rgba(0,0,0,0.1);
                                border-radius: 10px;
                                padding: 20px;
                                line-height: 20px;
                            ">{!! $product->translation('ka')->description !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if(in_array($product->supplier_id, [2, 4, 8, 11, 12]))
                @if($product->fullSpecifications)
                    <div class="col-12">
                        <div class="rounded collapsed" id="specification-section" style="padding: 15px; margin-top: 25px">
                            <div id="specs-wrapper" class="specs-collapsed @if(count($product->fullSpecifications) > 1) masonry-grid @endif">
                                @foreach($product->fullSpecifications as $full_specification_item)
                                    <div class="masonry-item p-1 rounded mb-3">
                                        <h3 class="h6 mb-3 font-neue">{{ $full_specification_item->name }}</h3>
                                        <ul class="list-unstyled d-flex flex-column gap-2 fs-sm m-0">
                                            @foreach($full_specification_item->list as $list_item)
                                                <li class="d-flex align-items-center position-relative pe-4">
                                                    <span>{{ $list_item->name }}:</span>
                                                    <span class="d-block flex-grow-1 border-bottom border-dashed mx-2"></span>
                                                    <span class="text-dark-emphasis fw-medium">{{ $list_item->value }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-4 justify-content-center"
                                    id="specs-toggle-btn">
                                სრული მახასიათებლები
                                <i class="ci-chevron-down ms-1"></i>
                            </button>
                        </div>
                    </div>
                @endif
            @endif
            <div class="col-12">
                @include('livewire.web.product.similar-products')
                @include('livewire.web.partials.installment-modal')
            </div>
        </div>
    </section>
    <style>
        #specification-section { overflow: hidden; }
        #specification-section.expanded { max-height: 2000px !important; }
        #specification-section.collapsed { max-height: 280px; }
        .masonry-grid { column-count: 2; overflow: hidden; }
        .masonry-item { display: inline-block; width: 100%; margin-bottom: 20px; break-inside: avoid; }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const wrapper = document.getElementById("specification-section");
            const btn = document.getElementById("specs-toggle-btn");
            btn.addEventListener("click", function () {
                wrapper.classList.toggle("expanded");
                if (wrapper.classList.contains("expanded")) {
                    btn.innerHTML = 'დამალვა <i class="ci-chevron-up ms-1"></i>';
                } else {
                    btn.innerHTML = 'სრული მახასიათებლები <i class="ci-chevron-down ms-1"></i>';
                }
            });
        });
    </script>
    <script src="https://webstatic.bog.ge/bog-sdk/bog-sdk.js?version=2&client_id=57315"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('bog:installment', (installment_data) => {
                BOG.Calculator.open({
                    bnpl: false,
                    amount: installment_data.amount,
                    onClose: () => {},
                    onRequest: (selected, successCb, closeCb) => {
                        const { amount, month, discount_code } = selected;
                        axios.post(installment_data.url, { amount, month, discount_code })
                            .then(r => successCb(r.data.orderId))
                            .catch(() => closeCb());
                        return false;
                    },
                    onComplete: () => false
                })
            });
            Livewire.on('bog:installment-part', (part_installment_data) => {
                BOG.Calculator.open({
                    bnpl: true,
                    amount: part_installment_data.amount,
                    onClose: () => {},
                    onRequest: (selected, successCb, closeCb) => {
                        const { amount, month, discount_code } = selected;
                        axios.post(part_installment_data.url, { amount, month, discount_code })
                            .then(r => successCb(r.data.orderId))
                            .catch(() => closeCb());
                        return false;
                    },
                    onComplete: () => false
                })
            });
        });
    </script>
</main>

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
                content_name: '{{ $product->translation('ka')->title }}',
                value: {{ $actualPrice }},
                currency: 'GEL',
                contents: [{ id: '{{ $product->id }}', quantity: 1 }]
            }, { eventID: '{{ $event_id }}' });
        </script>
    @endif
    <script>
        fbq('track', 'PageView', {}, { eventID: '{{ $eventId2 }}' });
    </script>
    <noscript><img height="1" width="1" style="display:none"
                   src="https://www.facebook.com/tr?id=1280014533998229&ev=PageView&noscript=1"/></noscript>
@endsection