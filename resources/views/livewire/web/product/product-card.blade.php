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
    <div class="pc-wrap"
         data-product-id="{{ $product->id }}"
         x-data="{
         loading: false,
         wishlistLoading: false,
         inWishlist: false,
         init() {
             this.$watch('$store.wishlist.ids', ids => {
                 this.inWishlist = ids.includes({{ $product->id }});
             });
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
                     if (window.Livewire) window.Livewire.dispatch('cartUpdated');
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

        {{-- Discount badge --}}
        @if(!empty($product->price->discount_percent))
            <div class="pc-badge">-{{ $product->price->discount_percent }}%</div>
        @endif

        {{-- Wishlist --}}
        <button type="button"
                class="pc-wishlist"
                @click="toggleWishlist()"
                :disabled="wishlistLoading"
                :aria-label="inWishlist ? 'სურვილების სიიდან წაშლა' : 'სურვილების სიაში დამატება'">
            <template x-if="!wishlistLoading">
                <i :class="inWishlist ? 'ci-heart-filled text-danger' : 'ci-heart'"></i>
            </template>
            <template x-if="wishlistLoading">
                <span class="spinner-border spinner-border-sm"></span>
            </template>
        </button>

        {{-- სურათი --}}
        <a class="pc-img-wrap" href="{{ route('web.products.view', $slug) }}">
            <img src="{{ $imageUrl }}"
                 alt="{{ $title }} — შეიძინე iapi.ge-ზე"
                 loading="lazy"
                 class="pc-img">
        </a>

        {{-- ინფო --}}
        <div class="pc-body">
            <a class="pc-title" href="{{ route('web.products.view', $slug) }}" title="{{ $title }}">
                {{ $title }}
            </a>

            <div class="pc-footer">
                <div class="pc-price">
                    @if(!empty($product->price->discount_price))
                        <span class="pc-price-now">{{ number_format($product->price->discount_price, 2) }} ₾</span>
                        <del class="pc-price-old">{{ number_format($product->price->regular_price, 2) }} ₾</del>
                    @else
                        <span class="pc-price-now">{{ number_format($product->price->regular_price, 2) }} ₾</span>
                    @endif
                </div>

                <button type="button"
                        class="pc-cart-btn"
                        @click="addToCart()"
                        :disabled="loading"
                        aria-label="კალათაში დამატება">
                    <template x-if="!loading">
                        <i class="ci-shopping-cart"></i>
                    </template>
                    <template x-if="loading">
                        <span class="spinner-border spinner-border-sm"></span>
                    </template>
                </button>
            </div>
        </div>
    </div>

    <style>
        .pc-wrap {
            position: relative;
            background: #fff;
            border-radius: 16px;
            border: 1.5px solid #eff0f2;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease;
        }
        .pc-wrap:hover {
            box-shadow: 0 10px 32px rgba(0,0,0,0.09);
            border-color: #e0e1e5;
            transform: translateY(-3px);
            z-index: 2;
            position: relative;
        }

        /* Badge */
        .pc-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 3;
            background: #ff3b3b;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            letter-spacing: .3px;
        }

        /* Wishlist */
        .pc-wishlist {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 3;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background .15s ease;
            backdrop-filter: blur(4px);
        }
        .pc-wishlist:hover { background: #fff; }
        .pc-wishlist i { font-size: 15px; color: #aaa; }
        .pc-wishlist i.text-danger { color: #e53935 !important; }

        /* სურათი */
        .pc-img-wrap {
            display: block;
            padding: 20px 20px 10px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
        }
        .pc-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            transition: transform .25s ease;
        }
        .pc-wrap:hover .pc-img { transform: scale(1.04); }

        /* Body */
        .pc-body {
            padding: 10px 16px 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
            gap: 10px;
        }

        /* Title */
        .pc-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.4;
            color: #1a1a1a;
            text-decoration: none;
            min-height: 2.8em;
            transition: color .15s ease;
        }
        .pc-title:hover { color: #ff6900; }

        /* Footer */
        .pc-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
        }

        /* Price */
        .pc-price { display: flex; flex-direction: column; gap: 2px; }
        .pc-price-now {
            font-size: 1.1rem;
            font-weight: 700;
            color: #ff6900;
            letter-spacing: -.02em;
            line-height: 1;
        }
        .pc-price-old {
            font-size: 11px;
            color: #aaa;
            text-decoration: line-through;
            line-height: 1;
        }

        /* Cart button */
        .pc-cart-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #ff6900;
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background .15s ease, transform .15s ease;
            flex-shrink: 0;
        }
        .pc-cart-btn:hover { background: #e55d00; transform: scale(1.07); }
        .pc-cart-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
        .pc-cart-btn i { font-size: 16px; }

        @media (max-width: 575px) {
            .pc-img-wrap { padding: 12px 12px 6px; }
            .pc-body { padding: 6px 10px 10px; }
            .pc-price-now { font-size: 1rem; }
            .pc-cart-btn { width: 32px; height: 32px; border-radius: 8px; }
            .pc-cart-btn i { font-size: 14px; }
        }
    </style>
@endif