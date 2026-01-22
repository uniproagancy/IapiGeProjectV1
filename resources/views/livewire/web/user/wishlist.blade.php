<div class="col-lg-12">
    <div class="ps-lg-13 ps-xl-0">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h2 mb-0 font-neue">სურვილების სია</h1>
        </div>
        @if($wishlistItems->count() > 0)
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                @foreach($wishlistItems as $product)
                    <div class="col" wire:key="wishlist-{{ $product->id }}">
                        <div class="product-card animate-underline hover-effect-opacity bg-body rounded position-relative">
                            <button type="button"
                                    wire:click="removeItem({{ $product->id }})"
                                    class="btn btn-icon btn-sm btn-secondary position-absolute top-0 end-0 mt-2 me-2 z-2"
                                    aria-label="წაშლა">
                                <i class="ci-close"></i>
                            </button>
                            <div class="position-relative">
                                <a class="d-block rounded-top overflow-hidden p-3 p-sm-4"
                                   href="{{ route('web.products.view', $product->translation('ka')->slug) }}">
                                    @if(!empty($product->price->discount_percent))
                                        <span class="badge bg-danger position-absolute top-0 start-0 mt-2 ms-2 z-1">
                                                        -{{ $product->price->discount_percent }}%
                                                    </span>
                                    @endif
                                    <div class="ratio" style="--cz-aspect-ratio: calc(240 / 258 * 100%)">
                                        <img src="{{ asset('storage/' . $product->main_image) }}"
                                             alt="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}"
                                             loading="lazy">
                                    </div>
                                </a>
                            </div>
                            <div class="w-100 min-w-0 px-1 pb-2 px-sm-3 pb-sm-3">
                                <h3 class="pb-1 mb-2">
                                    <a class="d-block fs-sm fw-medium text-truncate"
                                        href="{{ route('web.products.view', $product->translation('ka')->slug) }}">
                                        <span class="animate-target">
                                            {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
                                        </span>
                                    </a>
                                </h3>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    @if(!empty($product->price->discount_price))
                                        <div class="h5 lh-1 mb-0">
                                            {{ number_format($product->price->discount_price, 2) }} ₾
                                            <del class="text-body-tertiary fs-sm fw-normal">
                                                {{ number_format($product->price->regular_price, 2) }}
                                            </del>
                                        </div>
                                    @else
                                        <div class="h5 lh-1 mb-0">
                                            {{ number_format($product->price->regular_price, 2) }} ₾
                                        </div>
                                    @endif
                                </div>
                                <button type="button"
                                        wire:click="moveToCart({{ $product->id }})"
                                        class="btn btn-primary w-100 animate-slide-end font-neue"
                                        style="font-size: 12px"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="moveToCart({{ $product->id }})">
                                            <i class="ci-shopping-cart fs-base animate-target me-1"></i>
                                            დამატება
                                        </span>
                                        <span wire:loading wire:target="moveToCart({{ $product->id }})">
                                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                        </span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <svg class="text-muted mb-4" width="120" height="120" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <h3 class="h5 mb-2 font-neue">სურვილების სია ცარიელია</h3>
                <p class="text-muted mb-4">დაამატეთ პროდუქტები რომლებიც გაინტერესებთ</p>
                <a href="{{ route('web.products.index') }}"
                   class="btn btn-primary font-neue"
                   style="font-size: 12px">
                    <i class="ci-search me-2"></i>
                    პროდუქტების მოძებნა
                </a>
            </div>
        @endif
    </div>
</div>