@php
    $locale = app()->getLocale();
    $translation = $product->translations->where('locale', $locale)->first()
        ?? $product->translations->where('locale', 'ka')->first();

    $slug = $translation?->slug;
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
    <div class="product-card animate-underline hover-effect-opacity bg-body rounded">

        {{-- სურათის სექცია --}}
        <div class="position-relative">

            {{-- Wishlist ღილაკი - desktop --}}
            <div class="position-absolute top-0 end-0 z-2 hover-effect-target opacity-0 mt-3 me-3">
                <div class="d-flex flex-column gap-2">
                    <livewire:web.components.wishlist-button
                            :productId="$product->id"
                            class="btn-secondary animate-pulse"/>
                </div>
            </div>

            {{-- Dropdown - mobile --}}
            <div class="dropdown d-lg-none position-absolute top-0 end-0 z-2 mt-2 me-2">
                <button type="button"
                        class="btn btn-icon btn-sm btn-secondary bg-body"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="მეტი მოქმედებები">
                    <i class="ci-more-vertical fs-lg"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end fs-xs p-2" style="min-width: auto">
                    <li>
                        <a class="dropdown-item" href="#!">
                            <i class="ci-heart fs-sm ms-n1 me-2"></i>
                            სურვილების სია
                        </a>
                    </li>
                </ul>
            </div>

            <a class="d-block rounded-top overflow-hidden p-3 p-sm-4"
               href="{{ route('web.products.view', $slug) }}">

                {{-- ფასდაკლების badge --}}
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
        <div class="w-100 min-w-0 px-1 pb-2 px-sm-3 pb-sm-3">
            <h3 class="pb-1 mb-2">
                <a class="d-block fs-sm fw-medium text-truncate"
                   href="{{ route('web.products.view', $slug) }}">
                    <span class="animate-target">{{ $title }}</span>
                </a>
            </h3>

            <div class="d-flex align-items-center justify-content-between">
                @if(!empty($product->price->discount_price))
                    <div class="h5 lh-1 mb-0">
                        <span class="text-discount">{{ number_format($product->price->discount_price, 2) }} ₾</span>
                        <del class="text-body-tertiary fs-sm fw-normal">
                            {{ number_format($product->price->regular_price, 2) }} ₾
                        </del>
                    </div>
                @else
                    <div class="h5 lh-1 mb-0 text-discount">
                        {{ number_format($product->price->regular_price, 2) }} ₾
                    </div>
                @endif

                <livewire:web.components.add-to-cart-button
                        :productId="$product->id"
                        :productPrice="$price"
                        :productTitle="$title"/>
            </div>
        </div>
    </div>
@endif