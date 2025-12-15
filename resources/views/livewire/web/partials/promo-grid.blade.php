<section class="container pt-5 mt-1 mt-sm-2 mt-md-3 mt-lg-4">
    <!-- Section Title -->
    <div class="d-flex align-items-center justify-content-between pb-3 pb-md-4">
        <h2 class="h3 mb-0 font-neue" style="font-size: 22px">
            {{ $promo->translation(app()->getLocale())->title ?? $promo->translation('ka')->title }}
        </h2>
    </div>

    <div class="row">
        <!-- Promo Banner -->
        <div class="col-lg-4" data-bs-theme="dark">
            <div class="d-flex flex-column align-items-center justify-content-end h-100 text-center overflow-hidden rounded-5 px-4 px-lg-3 pt-4 pb-5"
                 style="background: #1d2c41 url({{ !empty($promo->background) ? asset('storage/' . $promo->background) : '' }}) center/cover no-repeat">
                <a class="btn btn-sm btn-primary font-neue"
                   href="{{ url($promo->url) }}">
                    სრული ჩამონათვალი
                    <i class="ci-arrow-up-right fs-base ms-1 me-n1"></i>
                </a>
            </div>
        </div>

        <!-- Products Grid - First Column -->
        <div class="col-sm-12 col-lg-4 d-flex flex-column gap-3 pt-4 py-lg-4">
            @foreach($promo->products->take(4) as $promoProduct)
                @include('livewire.web.partials.promo-product-item', [
                    'product' => $promoProduct->product
                ])
            @endforeach
        </div>

        <!-- Products Grid - Second Column -->
        <div class="col-sm-12 col-lg-4 d-flex flex-column gap-3 pt-4 py-lg-4">
            @foreach($promo->products->skip(4)->take(4) as $promoProduct)
                @include('livewire.web.partials.promo-product-item', [
                    'product' => $promoProduct->product
                ])
            @endforeach
        </div>
    </div>
</section>