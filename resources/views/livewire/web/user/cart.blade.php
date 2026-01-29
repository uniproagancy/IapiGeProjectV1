<div>
    <div class="col-lg-12">
        <div class="ps-lg-13 ps-xl-0">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1 class="h2 mb-0 font-neue">
                    ჩემი კალათა
                </h1>
                @if($cartItems->count() > 0)
                    <button type="button"
                            class="btn btn-outline-danger btn-sm font-neue"
                            wire:click="clearCart"
                            wire:loading.attr="disabled">
                        <i class="ci-trash me-2"></i>
                        კალათის გასუფთავება
                    </button>
                @endif
            </div>
            @if($cartItems->count() > 0)
                <div class="row">
                    <!-- Cart Items -->
                    <div class="col-lg-12">
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                            @foreach($cartItems as $item)
                                <div class="col" wire:key="cart-item-{{ $item->id }}">
                                    <div class="product-card animate-underline hover-effect-opacity bg-body rounded position-relative">
                                        <button type="button"
                                                wire:click="removeCartItem('{{ $item->id }}')"
                                                class="btn btn-icon btn-sm btn-secondary position-absolute top-0 end-0 mt-2 me-2 z-2"
                                                aria-label="წაშლა"
                                                wire:loading.attr="disabled"
                                                wire:target="removeCartItem">
                                        <span wire:loading.remove wire:target="removeCartItem('{{ $item->id }}')">
                                            <i class="ci-close"></i>
                                        </span>
                                            <span wire:loading wire:target="removeCartItem('{{ $item->id }}')">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </span>
                                        </button>
                                        <div class="position-relative">
                                            <a class="d-block rounded-top overflow-hidden p-3 p-sm-4"
                                               href="{{ route('web.products.view', $item->attributes['slug'] ?? '#') }}">
                                                @if($item->attributes['discount_percent'] ?? false)
                                                    <span class="badge bg-danger position-absolute top-0 start-0 mt-2 ms-2 z-1">
                                                    -{{ $item->attributes['discount_percent'] }}%
                                                </span>
                                                @endif
                                                <div class="ratio" style="--cz-aspect-ratio: calc(240 / 258 * 100%)">
                                                    <img src="{{ asset('storage/' . ($item->attributes['image'] ?? '')) }}"
                                                         alt="{{ $item->name }}"
                                                         loading="lazy"
                                                         onerror="this.src='{{ asset('images/placeholder.png') }}'">
                                                </div>
                                            </a>
                                        </div>
                                        <div class="w-100 min-w-0 px-1 pb-2 px-sm-3 pb-sm-3">
                                            <h3 class="pb-1 mb-2">
                                                <a class="d-block fs-sm fw-medium text-truncate"
                                                   href="{{ route('web.products.view', $item->attributes['slug'] ?? '#') }}">
                                                <span class="animate-target">
                                                    {{ $item->name }}
                                                </span>
                                                </a>
                                            </h3>
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <div class="h5 lh-1 mb-0">
                                                    {{ number_format($item->price, 2) }} ₾
                                                    @if($item->attributes['discount_percent'] ?? false)
                                                        <del class="text-body-tertiary fs-sm fw-normal">
                                                            {{ number_format($item->attributes['regular_price'] ?? $item->price, 2) }}
                                                            ₾
                                                        </del>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="countInput rounded-2 flex-grow-1">
                                                    <button type="button"
                                                            class="btn btn-icon btn-sm"
                                                            wire:click="decrementQuantity('{{ $item->id }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="decrementQuantity"
                                                            aria-label="რაოდენობის შემცირება"
                                                            {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                                        <i class="ci-minus"></i>
                                                    </button>
                                                    <input type="number"
                                                           class="form-control form-control-sm"
                                                           value="{{ $item->quantity }}"
                                                           readonly>
                                                    <button type="button"
                                                            class="btn btn-icon btn-sm"
                                                            wire:click="incrementQuantity('{{ $item->id }}')"
                                                            wire:loading.attr="disabled"
                                                            wire:target="incrementQuantity"
                                                            aria-label="რაოდენობის გაზრდა">
                                                        <i class="ci-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="d-flex flex-column gap-2 mb-4 align-items-end">
                            <a href="{{ route('web.checkout.index') }}"
                               class="btn btn-primary font-neue">
                                ყიდვა
                                <i class="ci-arrow-right me-2"></i>
                            </a>
                            <a href="{{ route('web.products.index') }}"
                               class="btn btn-secondary font-neue">
                                <i class="ci-arrow-left me-2"></i>
                                მაღაზიაში დაბრუნება
                            </a>
                        </div>
                    </div>
                </div>
        </div>
        @else
            <div class="row">
                <div class="col-lg-12">
                    <div class="ps-lg-13 ps-xl-0">
                        <div class="text-center py-5">
                            <i class="ci-shopping-cart opacity-75 mb-5" style="font-size: 100px"></i>
                            <h3 class="h5 mb-2 font-neue">კალათა ცარიელია</h3>
                            <a href="{{ route('web.products.index') }}"
                               class="btn btn-primary font-neue"
                               style="font-size: 12px">
                                <i class="ci-search me-2"></i>
                                დაიწყე შოპინგი
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
<style>
    .countInput {
        display: flex;
        align-items: center;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        background: #f8f9fa;
        overflow: hidden;
    }

    .countInput input {
        border: none;
        background: transparent;
        text-align: center;
        padding: 0.25rem 0.5rem;
        flex: 1;
    }

    .countInput input:focus {
        box-shadow: none;
        background: transparent;
    }

    .countInput .btn {
        padding: 0.25rem 0.5rem;
        border: none;
        margin: 0;
        background: transparent;
        color: inherit;
    }

    .countInput .btn:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .countInput .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .product-card {
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .product-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .product-card img {
        object-fit: cover;
        width: 100%;
        height: 100%;
    }

    .badge {
        font-size: 11px;
        padding: 0.35rem 0.6rem;
    }

    @media (max-width: 992px) {
        .product-card .ratio {
            --cz-aspect-ratio: calc(200 / 220 * 100%);
        }
    }

    @media (max-width: 768px) {
        .row-cols-md-3 {
            --bs-columns: 2;
        }

        .position-sticky {
            position: relative !important;
            top: auto !important;
            margin-top: 2rem;
        }
    }

    @media (max-width: 576px) {
        .ps-lg-13 {
            padding-left: 0 !important;
        }

        .row-cols-sm-2 {
            --bs-columns: 1;
        }

        .product-card {
            margin-bottom: 1rem;
        }

        .d-flex.gap-2 {
            flex-wrap: wrap;
        }
    }
</style>
</div>