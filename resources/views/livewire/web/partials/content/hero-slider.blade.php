<section class="position-relative">
    <div class="swiper position-absolute top-0 start-0 w-100 h-100 p-2"
         data-swiper='{
            "effect": "fade",
            "loop": true,
            "speed": 400,
            "pagination": {
                "el": ".swiper-pagination",
                "clickable": true
            },
            "autoplay": {
                "delay": 5500,
                "disableOnInteraction": false
            }
         }'
         data-bs-theme="dark">
        <div class="swiper-wrapper">
            @foreach($this->sliders as $slider)
                <div class="swiper-slide rounded-5 overflow-hidden">
                    <a href="{{ $slider->url ?? '' }}" class="d-block h-100">
                        <img src="{{ asset('storage'.$slider->path) }}"
                             class="w-100 h-100 object-fit-cover rtl-flip"
                             alt="{{ $slider->title ?? ('IAPI.GE — აქცია ' . ($loop->index + 1)) }}"
                             @if($loop->first)
                                 loading="eager"
                             fetchpriority="high"
                             @else
                                 loading="lazy"
                                @endif>
                    </a>
                </div>
            @endforeach
        </div>
        <div class="swiper-pagination pb-sm-2"></div>
    </div>

    <div class="d-md-none" style="height: 100px"></div>
    <div class="d-none d-md-block d-lg-none" style="height: 150px"></div>
    <div class="d-none d-lg-block d-xl-none" style="height: 200px"></div>
    <div class="d-none d-xl-block d-xxl-none" style="height: 250px"></div>
    <div class="d-none d-xxl-block" style="height: 300px"></div>
</section>