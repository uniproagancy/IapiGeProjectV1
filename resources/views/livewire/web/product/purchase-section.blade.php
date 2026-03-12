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
    <div class="d-none d-lg-block">
        <div class="d-flex flex-wrap flex-sm-nowrap flex-md-wrap flex-lg-nowrap gap-3 gap-lg-1 gap-xl-1 mb-4">
            <a href="{{ route('web.checkout.index', ['product_id' => $product->id]) }}" class="btn btn-lg btn-primary w-100 animate-slide-end font-neue"
                    style="font-size: 14px">
                ყიდვა / განვადება
            </a>
        </div>
        @if(!empty($product->price->discount_price) && $product->price->discount_price > 100 OR $product->price->regular_price > 100)
        <div class="d-flex flex-wrap flex-sm-nowrap flex-md-wrap flex-lg-nowrap gap-3 gap-lg-1 gap-xl-1 mb-4">
            <span class="badge text-bg-success">თვეში @if(!empty($product->price->discount_price))
                    {{ number_format($product->price->discount_price / 24) }} @else {{ number_format($product->price->regular_price / 24) }} @endif ₾ -დან
            </span>
        </div>
        @endif
    </div>
    @foreach($product->variations as $variation)
        @if($variation->show === 1)
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
        @endif
    @endforeach
    @include('livewire.web.product.delivery-info')
    @if(Route::is('web.products.view'))
{{--        <nav class="navbar navbar-expand navbar-dark bg-dark fixed-bottom d-lg-none bottom-nav-top"--}}
{{--             style="padding: 0.75rem 0; border-top: 1px solid #333; z-index: 1000;">--}}
{{--            <div class="container px-2">--}}
{{--                <div class="d-flex w-100 justify-content-between align-items-center"  style="margin-bottom: 20px;">--}}
{{--                    <div>--}}
{{--                        <div class="d-flex flex-wrap align-items-center">--}}
{{--                            @if(!empty($product->price->discount_price))--}}
{{--                                <div class="h4 lh-1 mb-0" style="color: #fff;">--}}
{{--                                    {{ number_format($product->price->discount_price, 2) }} ₾--}}
{{--                                    <del class="text-body-tertiary fs-sm fw-normal" style="color: #252525 !important;">--}}
{{--                                        {{ number_format($product->price->regular_price, 2) }}--}}
{{--                                    </del>--}}
{{--                                </div>--}}
{{--                            @else--}}
{{--                                <div class="h4 lh-1 mb-0" style="color: #fff;">--}}
{{--                                    {{ number_format($product->price->regular_price, 2) }} ₾--}}
{{--                                </div>--}}
{{--                            @endif--}}
{{--                            @if(!empty($product->price->discount_price) && $product->price->discount_price > 100 OR $product->price->regular_price > 100)--}}
{{--                                <div class="d-flex flex-wrap flex-sm-nowrap flex-md-wrap flex-lg-nowrap gap-3 gap-lg-1 gap-xl-1" style="margin-bottom: 6px">--}}
{{--                                    <span class="badge text-bg-success">თვეში @if(!empty($product->price->discount_price))--}}
{{--                                        {{ number_format($product->price->discount_price / 24) }} @else {{ number_format($product->price->regular_price / 24) }} @endif ₾ -დან--}}
{{--                                    </span>--}}
{{--                                </div>--}}
{{--                            @endif--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="d-flex justify-content-end">--}}
{{--                        <livewire:web.components.add-to-cart-button-white :productId="$product->id"/>--}}
{{--                        <a href="{{ route('web.checkout.index', ['product_id' => $product->id]) }}" class="btn btn-lg btn-dark w-100 animate-slide-end font-neue">--}}
{{--                            ყიდვა / განვადება--}}
{{--                        </a>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </nav>--}}
    @endif
</div>