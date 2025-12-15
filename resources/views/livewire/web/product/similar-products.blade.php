<section class="container pt-5 mt-2 mt-sm-3 mt-lg-4">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 pb-md-2">
        <h2 class="h3 mb-0 font-neue" style="font-size: 16px">მსგავსი პროდუქცია</h2>
    </div>
    <div class="swiper my-swiper"
         data-section="similar-{{ $product->id }}"
         x-data="swiperInit('similar-{{ $product->id }}')">
        <div class="swiper-wrapper">
            @foreach($similarProducts as $similarProduct)
                <div class="swiper-slide" wire:key="similar-{{ $similarProduct->id }}">
                    @include('livewire.web.product.product-card', ['product' => $similarProduct])
                </div>
            @endforeach
        </div>
        <div class="swiper-prev" data-section="similar-{{ $product->id }}">
            <i class="ci-chevron-left"></i>
        </div>
        <div class="swiper-next" data-section="similar-{{ $product->id }}">
            <i class="ci-chevron-right"></i>
        </div>
    </div>
</section>
<style>
    .swiper-prev, .swiper-next {
        width: 40px;
        height: 40px;
        background: #eef1f6;
        color: #252525;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        cursor: pointer;
        transition: color .25s ease-in-out, background-color .25s ease-in-out;
    }

    .swiper-prev {
        left: 0;
    }

    .swiper-next {
        right: 0;
    }

    .swiper-prev:hover, .swiper-next:hover {
        background: #f2223b;
        color: #ffffff;
    }
</style>