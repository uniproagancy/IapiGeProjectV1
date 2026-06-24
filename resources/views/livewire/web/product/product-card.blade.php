@php
    $locale = app()->getLocale();
    $translation = $product->translations->where('locale', $locale)->first()
        ?? $product->translations->where('locale', 'ka')->first();

    $slug  = $translation?->slug;
    $title = $translation?->title;

    $price = !empty($product->price?->discount_price)
        ? $product->price->discount_price
        : $product->price?->regular_price;

    $imageUrl = $product->main_image && $product->main_image != 1
        ? asset('storage/' . $product->main_image)
        : (!empty($product->images[0]->path)
            ? asset('storage/' . $product->images[0]->path)
            : asset('web-assets/img/no-product.png'));
@endphp

@if(!empty($slug))
    <div class="product-card animate-underline hover-effect-opacity bg-body rounded h-100 d-flex flex-column"
         data-product-id="{{ $product->id }}"
         x-data="{
             loading: false,
             wishlistLoading: false,
             inWishlist: false,
             init() {
                 // wishlist სტატუსი batch-ით იტვირთება (იხ. swiper-init.blade.php)
                 this.$watch('$store.wishlist.ids', ids => {
                     this.inWishlist = ids.includes({{ $product->id }});
                 });
                 // საწყისი სტატუსი store-დან
                 if (window.Alpine && Alpine.store('wishlist')) {
                     this.inWishlist = Alpine.store('wishlist').ids.includes({{ $product->id }});
                 }
             },
             addToCart() {
                 if (this.loading) return;
                 this.loading = true;
                 fetch('/cart/add', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     },
                     body: JSON.stringify({
                         product_id: {{ $product->id }},
                         quantity: 1,
                         source_url: window.location.href,
                     }),
                 })
                 .then(r => r.json())
                 .then(data => {
                     if (data.success) {
                         window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                         window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                         // Facebook Pixel
                         if (typeof fbq !== 'undefined') {
                             fbq('track', 'AddToCart', {
                                 content_ids: [data.product_id],
                                 content_type: 'product',
                                 value: data.price * data.quantity,
                                 currency: 'GEL',
                             }, { eventID: data.event_id });
                         }
                     } else {
                         window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'error' } }));
                     }
                 })
                 .catch(() => {
                     window.dispatchEvent(new CustomEvent('notify', { detail: { message: 'შეცდომა!', type: 'error' } }));
                 })
                 .finally(() => { this.loading = false; });
             },
             toggleWishlist() {
                 if (this.wishlistLoading) return;
                 this.wishlistLoading = true;
                 fetch('/wishlist/toggle', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     },
                     body: JSON.stringify({ product_id: {{ $product->id }} }),
                 })
                 .then(r => r.json())
                 .then(data => {
                     if (data.auth === false) {
                         window.location.href = data.redirect;
                         return;
                     }
                     if (data.success) {
                         this.inWishlist = data.in_wishlist;
                         window.dispatchEvent(new CustomEvent('notify', { detail: { message: data.message, type: 'success' } }));
                     }
                 })
                 .finally(() => { this.wishlistLoading = false; });
             }
         }">

        {{-- სურათის სექცია --}}
        <div class="position-relative">

            {{-- Wishlist ღილაკი --}}
            <div class="position-absolute top-0 end-0 z-2 mt-3 me-3">
                <button type="button"
                        @click="toggleWishlist()"
                        :disabled="wishlistLoading"
                        class="btn btn-sm"
                        :aria-label="inWishlist ? 'სურვილების სიიდან წაშლა' : 'სურვილების სიაში დამატება'">
                    <template x-if="!wishlistLoading">
                        <i :class="inWishlist ? 'ci-heart-filled text-danger' : 'ci-heart'" class="fs-sm animate-target"></i>
                    </template>
                    <template x-if="wishlistLoading">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </template>
                </button>
            </div>

            <a class="d-block rounded-top overflow-hidden p-3 p-sm-4"
               href="{{ route('web.products.view', $slug) }}">

                @if(!empty($product->price->discount_percent))
                    <span class="badge bg-danger position-absolute top-0 start-0 mt-2 ms-2 mt-lg-3 ms-lg-3 z-2">
                        -{{ $product->price->discount_percent }}%
                    </span>
                @endif

                <div class="ratio" style="--cz-aspect-ratio: calc(240 / 258 * 100%)">
                    <img src="{{ $imageUrl }}"
                         alt="{{ $title }} — შეიძინე iapi.ge-ზე"
                         loading="lazy"
                         style="object-fit: contain; width: 100%; height: 100%; mix-blend-mode: multiply;">
                </div>
            </a>
        </div>

        {{-- ინფო სექცია --}}
        <div class="w-100 min-w-0 px-1 pb-2 px-sm-3 pb-sm-3 d-flex flex-column flex-grow-1">
            <h3 class="pb-1 mb-2">
                <a class="d-block fs-sm fw-medium"
                   href="{{ route('web.products.view', $slug) }}"
                   title="{{ $title }}"
                   style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;line-height:1.35;height:2.7em;color:inherit;">
                    {{ $title }}
                </a>
            </h3>

            <div class="d-flex align-items-end justify-content-between mt-auto">
                @if(!empty($product->price->discount_price))
                    <div class="lh-1 mb-0">
                        <span class="text-discount fw-bold" style="font-size:1.15rem;letter-spacing:-0.01em;">
                            {{ number_format($product->price->discount_price, 2) }} ₾
                        </span>
                        <del class="text-body-tertiary fs-sm fw-normal d-block mt-1">
                            {{ number_format($product->price->regular_price, 2) }} ₾
                        </del>
                    </div>
                @else
                    <div class="lh-1 mb-0">
                        <span class="text-discount fw-bold" style="font-size:1.15rem;letter-spacing:-0.01em;">
                            {{ number_format($product->price->regular_price, 2) }} ₾
                        </span>
                    </div>
                @endif

                {{-- კალათის ღილაკი — Alpine.js --}}
                <button type="button"
                        @click="addToCart()"
                        :disabled="loading"
                        class="product-card-button btn btn-icon btn-primary animate-slide-end ms-2"
                        aria-label="კალათაში დამატება">
                    <template x-if="!loading">
                        <i class="ci-shopping-cart fs-base animate-target"></i>
                    </template>
                    <template x-if="loading">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </template>
                </button>
            </div>
        </div>
    </div>
@endif