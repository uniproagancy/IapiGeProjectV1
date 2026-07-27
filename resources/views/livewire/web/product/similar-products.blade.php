<section class="container pt-5 mt-2 mt-sm-3 mt-lg-4 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 pb-md-2">
        <h2 class="h3 mb-0 font-neue" style="font-size: 16px">მსგავსი პროდუქცია</h2>
        <div class="nav ms-3">
            @if(!empty($product->category))
            <a class="nav-link animate-underline px-0 py-2"
               href="{{ route('web.products.index', [
                   'category_slug' => $product->category->translation('ka')->slug
               ]) }}">
                <span class="animate-target">სრული ჩამონათვალი</span>
                <i class="ci-chevron-right fs-base ms-1"></i>
            </a>
            @endif
        </div>
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