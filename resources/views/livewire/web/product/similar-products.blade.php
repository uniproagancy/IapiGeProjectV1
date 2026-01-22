<section class="container pt-5 mt-2 mt-sm-3 mt-lg-4 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 pb-md-2">
        <h2 class="h3 mb-0 font-neue" style="font-size: 16px">მსგავსი პროდუქცია</h2>
    </div>
    <div class="product-swiper position-relative" data-section="{{ $product->id }}">
        <div class="swiper-wrapper">
            @foreach($similarProducts as $similarProduct)
                <div class="swiper-slide" wire:key="similar-{{ $similarProduct->id }}">
                    @include('livewire.web.product.product-card', ['product' => $similarProduct])
                </div>
            @endforeach
        </div>
        <div class="swiper-prev" data-section="{{ $product->id }}">
            <i class="ci-chevron-left"></i>
        </div>
        <div class="swiper-next" data-section="{{ $product->id }}">
            <i class="ci-chevron-right"></i>
        </div>
    </div>
</section>