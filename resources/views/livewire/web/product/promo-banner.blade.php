<section class="container pt-5 mt-sm-2 mt-md-3 mt-lg-4">
    <div class="row g-0">
        <div class="col-md-12 position-relative">
            <div class="position-relative">
                <span class="position-absolute top-0 start-0 w-100 h-100 rounded-5 d-none-dark rtl-flip"
                      style="background: linear-gradient(90deg, #accbee 0%, #e7f0fd 100%)"></span>
                <span class="position-absolute top-0 start-0 w-100 h-100 rounded-5 d-none d-block-dark rtl-flip"
                      style="background: linear-gradient(90deg, #1b273a 0%, #1f2632 100%)"></span>
                <div class="row align-items-center position-relative z-2">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="text-center text-md-start py-md-5 px-4 ps-md-5 pe-md-0 me-md-n5">
                            <h3 class="text-uppercase fw-bold ps-xxl-3 pb-2 mb-1">
                                {{ $promo->translation(app()->getLocale())->title ?? $promo->translation('ka')->title }}
                            </h3>
                            <p class="text-body-emphasis ps-xxl-3 mb-0">
                                {{ $promo->translation(app()->getLocale())->description ?? $promo->translation('ka')->description }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex justify-content-center justify-content-md-end pb-5 pb-md-0">
                        <div class="me-xxl-4">
                            @if(!empty($promo->image))
                                <img src="{{ asset('storage/' . $promo->image) }}"
                                     class="d-block rtl-flip"
                                     width="420"
                                     alt="{{ $promo->translation(app()->getLocale())->title ?? $promo->translation('ka')->title }}">
                            @endif
                            <div class="d-none d-lg-block" style="margin-bottom: -9%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-none d-lg-block" style="padding-bottom: 3%"></div>
</section>