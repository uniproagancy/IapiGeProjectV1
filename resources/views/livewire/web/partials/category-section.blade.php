<section class="container pt-5 mt-2 mt-sm-3 mt-lg-4">
    <div class="d-flex align-items-center justify-content-between pb-2 pb-md-2">
        <h2 class="h3 mb-0 font-neue" style="font-size: 16px">
            {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
        </h2>
        <div class="nav ms-3">
            <a class="nav-link animate-underline px-0 py-2"
               href="{{ route('web.products.index', [
                   'category_slug' => $category->translation()->slug ?? $category->translation('ka')->slug
               ]) }}">
                <span class="animate-target">სრული ჩამონათვალი</span>
                <i class="ci-chevron-right fs-base ms-1"></i>
            </a>
        </div>
    </div>
    <div class="position-relative py-2">
        <div class="product-swiper overflow-hidden" data-section="{{ $category->id }}">
            <div class="swiper-wrapper">
                @foreach($products as $product)
                    <div class="swiper-slide" wire:key="home-product-{{ $product->id }}">
                        @include('livewire.web.product.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
            <div class="swiper-prev" data-section="{{ $category->id }}">
                <i class="ci-chevron-left"></i>
            </div>
            <div class="swiper-next" data-section="{{ $category->id }}">
                <i class="ci-chevron-right"></i>
            </div>
        </div>
    </div>
</section>