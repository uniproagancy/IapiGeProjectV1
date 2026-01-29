<div>
    <section class="container pt-5 mt-2 mt-sm-3 mt-lg-4 mt-xl-5 mb-n3">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 pt-lg-2 pt-xl-0">
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-map-pin fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">{{ __('trans.store_location') }}</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled">
                    <li>{{ __('trans.address_city') }}</li>
                    <li>{{ __('trans.address_street') }}</li>
                </ul>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-phone-outgoing fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">{{ __('trans.call_us_directly') }}</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled text-center" style="font-size: 14px;">
                    <li class="d-flex justify-content-between">
                        <span>ტელ:</span>
                        <span>+995 500 111 111</span>
                    </li>
                </ul>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-mail fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">{{ __('trans.send_message') }}</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled">
                    <li><a href="mailto:help@example.com">help@example.com</a></li>
                </ul>
            </div>
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="ci-clock fs-lg text-dark-emphasis"></i>
                    <h3 class="h6 ps-2 ms-1 mb-0 font-neue">{{ __('trans.working_hours') }}</h3>
                </div>
                <hr class="text-dark-emphasis opacity-50 my-3 my-md-4">
                <ul class="list-unstyled">
                    <li>{{ __('trans.weekdays') }}: 8:00 - 18:00</li>
                    <li>{{ __('trans.weekends') }}: 10:00 - 16:00</li>
                </ul>
            </div>
        </div>
    </section>
    <section class="container py-5 my-2 my-sm-3 my-lg-4 my-xl-5">
        <div class="d-sm-flex align-items-center justify-content-between py-xxl-3">
            <div class="mb-4 mb-sm-0 me-sm-4">
                <h2 class="h3">{{ __('trans.looking_for_support') }}</h2>
                <p class="mb-0">{{ __('trans.support_description') }}</p>
            </div>
            <a class="btn btn-lg btn-outline-dark" href="">
                {{ __('trans.help_center') }}
            </a>
        </div>
    </section>
</div>