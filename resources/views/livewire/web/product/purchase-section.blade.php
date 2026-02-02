<div class="ps-md-4 ps-xl-0">
    <div class="d-flex flex-wrap align-items-center mb-3">
        @if(!empty($product->price->discount_price))
            <div class="h4 lh-1 mb-0">
                {{ number_format($product->price->discount_price, 2) }} ₾
                <del class="text-body-tertiary fs-sm fw-normal">
                    {{ number_format($product->price->regular_price, 2) }}
                </del>
            </div>
        @else
            <div class="h4 lh-1 mb-0">
                {{ number_format($product->price->regular_price, 2) }} ₾
            </div>
        @endif
        @if($product->in_stock === 1)
            <div class="d-flex align-items-center text-success fs-sm ms-auto font-neue">
                <i class="ci-check-circle fs-base me-2"></i>
                მარაგშია
            </div>
        @endif
    </div>
    <livewire:web.components.add-to-cart-button-quantity :productId="$product->id"/>
    <div class="d-flex flex-wrap flex-sm-nowrap flex-md-wrap flex-lg-nowrap gap-3 gap-lg-1 gap-xl-1 mb-4">
        <button type="button"
                class="btn btn-lg btn-outline-dark w-100 animate-slide-end font-neue"
                style="font-size: 14px"
                @click="$wire.dispatch('openCheckoutModal', [{{ $product->id }}]); setTimeout(() => { new bootstrap.Modal(document.getElementById('checkoutModal')).show(); }, 100);">
            სწრაფი შეძენა
        </button>
    </div>
    @foreach($product->variations as $variation)
        <div class="mb-4">
            <div class="d-flex">
                <small class="px-1">{{ $variation->name }}: </small>
                <label class="form-label fw-semibold pb-1 mb-2 font-neue">{{ $variation->value }}</label>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($variation->items as $item)
                    @if(!empty($item->product))
                        @php
                            $slug = \App\Models\Product\ProductTranslation::where('product_id', $item->product->id)->where('locale', 'ka')->first();
                        @endphp
                        @if($item->is_color === 0)
                            <a href="{{ route('web.products.view', $slug->slug) }}"
                               class="btn btn-outline-secondary @if($item->supplier_product_id === $product->supplier_product_id) active @endif">{{ $item->value }}</a>
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
    @include('livewire.web.product.delivery-info')
</div>