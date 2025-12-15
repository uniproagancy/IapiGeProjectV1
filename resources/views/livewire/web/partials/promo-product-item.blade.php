<div class="position-relative animate-underline d-flex align-items-center ps-xl-3">
    <div class="ratio ratio-1x1 flex-shrink-0" style="width: 110px">
        <img src="{{ asset('storage/' . $product->main_image) }}"
             alt="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}"
             loading="lazy">
    </div>

    <div class="w-100 min-w-0 ps-2 ps-sm-3">
        <h4 class="mb-2">
            <a class="stretched-link d-block fs-sm fw-medium text-truncate"
               href="{{ route('web.products.view', $product->translation(app()->getLocale())->slug ?? $product->translation('ka')->slug) }}">
                <span class="animate-target">
                    {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
                </span>
            </a>
        </h4>

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
            class="product-card-button btn btn-icon btn-danger animate-slide-end ms-2"
            aria-label="კალათაში დამატება">
        <i class="ci-shopping-cart fs-base animate-target"></i>
    </button>
</div>