<div class="product-card-compact position-relative animate-underline d-flex align-items-center ps-xl-3">
    <div class="ratio ratio-1x1 flex-shrink-0 rounded overflow-hidden bg-body-tertiary" style="width: 96px">
        <img src="{{ asset('storage/' . $product->main_image) }}"
             alt="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}"
             loading="lazy"
             style="object-fit: contain; mix-blend-mode: multiply; padding: 6px;">
    </div>

    <div class="w-100 min-w-0 ps-2 ps-sm-3">
        <h4 class="mb-2">
            <a class="stretched-link d-block fs-sm fw-medium product-compact-title"
               href="{{ route('web.products.view', $product->translation(app()->getLocale())->slug ?? $product->translation('ka')->slug) }}"
               title="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}">
                <span class="animate-target">
                    {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
                </span>
            </a>
        </h4>

        @if(!empty($product->price->discount_price))
            <div class="d-flex align-items-baseline gap-2 lh-1 mb-0">
                <span class="fs-5 fw-bold text-discount">{{ number_format($product->price->discount_price, 2) }} ₾</span>
                <del class="text-body-tertiary fs-sm fw-normal">
                    {{ number_format($product->price->regular_price, 2) }} ₾
                </del>
            </div>
        @else
            <div class="fs-5 fw-bold lh-1 mb-0">
                {{ number_format($product->price->regular_price, 2) }} ₾
            </div>
        @endif
    </div>

    <button type="button"
            class="product-card-button btn btn-icon btn-primary animate-slide-end ms-2 flex-shrink-0"
            aria-label="კალათაში დამატება">
        <i class="ci-shopping-cart fs-base animate-target"></i>
    </button>
</div>