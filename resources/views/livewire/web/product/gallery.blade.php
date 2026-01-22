<div class="product-gallery d-flex gap-3">
    <div class="swiper thumbs-swiper" id="thumbs" style="margin: 10px 0 0 0">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <img src="{{ asset('storage/'.$product->main_image) }}"
                     class="thumb-img"
                     loading="lazy"
                     style="width: 100%; height: 100%; object-fit: cover; display: block;">
            </div>
            @foreach($product->images as $image)
                <div class="swiper-slide">
                    <img src="{{ asset('storage/'.$image->path) }}"
                         class="thumb-img"
                         loading="lazy"
                         style="width: 100%; height: 100%; object-fit: cover; display: block;">
                </div>
            @endforeach
        </div>
    </div>
    <div class="swiper main-swiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <img src="{{ asset('storage/'.$product->main_image) }}"
                     class="main-img"
                     loading="lazy"
                     style="width: 100%; height: auto; object-fit: contain; max-height: 465px;">
            </div>
            @foreach($product->images as $image)
                <div class="swiper-slide">
                    <img src="{{ asset('storage/'.$image->path) }}"
                         class="main-img"
                         loading="lazy"
                         style="width: 100%; height: auto; object-fit: contain; max-height: 465px;">
                </div>
            @endforeach
        </div>
        <button class="btn slider-prev"><i class="ci-chevron-left"></i></button>
        <button class="btn slider-next"><i class="ci-chevron-right"></i></button>
    </div>
</div>

<style>
    .product-gallery {
        align-items: flex-start;
    }

    .thumbs-swiper {
        width: 80px;
        height: 460px;
    }

    .thumbs-swiper .swiper-slide {
        border-radius: 10px;
        padding: 5px;
        cursor: pointer;
        opacity: .5;
    }

    .thumbs-swiper .swiper-slide-thumb-active {
        opacity: 1;
        border: 2px solid #f2223b;
        border-radius: 10px;
    }

    .thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .main-swiper {
        width: 480px;
        position: relative;
    }

    .main-img {
        width: 100%;
        height: auto;
        border-radius: 16px;
    }

    .slider-prev, .slider-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 50;
        background: #fff;
        border-radius: 50%;
        padding: 8px 10px;
    }

    .slider-prev {
        left: 10px;
    }

    .slider-next {
        right: 10px;
    }
</style>
@section('page_scripts')
    <script>
        const thumbs = new Swiper('#thumbs', {
            direction: 'vertical',
            slidesPerView: 5,
            spaceBetween: 10,
            watchSlidesProgress: true,
        });
        const mainSwiper = new Swiper('.main-swiper', {
            loop: true,
            spaceBetween: 10,
            navigation: {
                nextEl: '.slider-next',
                prevEl: '.slider-prev',
            },
            thumbs: {
                swiper: thumbs,
            }
        });
    </script>
    @include('livewire.web.product.swiper-init')
@endsection
