@section('seo')
    <title>IAPI.GE — შეიძინე იაფად | iapi.ge</title>
    <meta name="keywords"
          content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

@php
    /**
     * ბანერების კონფიგი — დაამატე / შეცვალე რამდენიც გინდა
     *
     * after_index  — რომელი კატეგორიის შემდეგ გამოჩნდეს (0-დან იწყება)
     * image        — სურათის პათი public_path-დან
     * link         — ბანერის ლინკი
     * alt          — alt ტექსტი
     */
    $banners = [
        [
            'after_index' => 2,
            'image'       => asset('banners/banner1.jpg'),
            'link'        => '/promotions',
            'alt'         => 'აქცია',
        ],
        [
            'after_index' => 5,
            'image'       => asset('banners/banner2.jpg'),
            'link'        => '/sale',
            'alt'         => 'ფასდაკლება',
        ],
        // დამატება: ['after_index' => 8, 'image' => asset('banners/banner3.jpg'), 'link' => '/...', 'alt' => '...'],
    ];

@endphp

<div>
    @include('livewire.web.partials.content.hero-slider')
    @include('livewire.web.partials.features')
    @include('livewire.web.partials.sections-carousel', ['sections' => $homeSections, 'heading' => 'საზაფხულო შემოთავაზება'])

    @foreach($this->promotions->where('position', 1) as $promo)
        @if($promo->type === 1)
            @include('livewire.web.product.promo-grid', ['promo' => $promo])
        @endif
    @endforeach

    @foreach($this->productCategories->where('parent_id', 0) as $index => $category)

        {{-- ბრენდები — კატეგორიების შუაში --}}
        @if(count($this->productCategories->where('parent_id', 0)) / 2 === $index)
            <div style="background: #ff6900; padding: 0 0 25px;">
                <section class="container py-4 mt-sm-3 mt-lg-5">
                    <div class="position-relative">
                        <div class="product-swiper overflow-hidden" data-section="brand-{{ $index }}">
                            <div class="swiper-wrapper">
                                @foreach($this->brands as $brand)
                                    @if(file_exists(public_path('web-assets/brands/' . $brand->id . '.svg')))
                                        <div class="swiper-slide" wire:key="brand-item-{{ $brand->id }}">
                                            <a class="btn btn-outline-secondary w-100 rounded-4 p-3"
                                               href="{{ route('web.products.index', ['brands[0]' => $brand->id]) }}"
                                               style="height: 100px; background: #fff;">
                                                <img src="{{ asset('web-assets/brands/' . $brand->id . '.svg') }}"
                                                     class="d-none-dark" alt="{{ $brand->name }}" style="height: 80px;">
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-prev" data-section="brand-{{ $index }}"><i class="ci-chevron-left"></i></div>
                        <div class="swiper-next" data-section="brand-{{ $index }}"><i class="ci-chevron-right"></i></div>
                    </div>
                </section>
            </div>
        @endif

        {{-- კატეგორიის პროდუქტები --}}
        @php $products = $category->getActiveProducts()->take(20) @endphp
        @include('livewire.web.partials.category-section', [
            'category' => $category,
            'products' => $products,
        ])

        {{-- ბანერები ამ კატეგორიის შემდეგ --}}
        @php $bannersHere = collect($banners)->where('after_index', $index); @endphp
        @if($bannersHere->isNotEmpty())
            <div class="container py-3">
                @foreach($bannersHere as $banner)
                    <a href="{{ $banner['link'] }}" class="d-block mb-3">
                        <img src="{{ $banner['image'] }}"
                             alt="{{ $banner['alt'] }}"
                             class="w-100 rounded-4"
                             style="object-fit: cover; max-height: 200px;">
                    </a>
                @endforeach
            </div>
        @endif

    @endforeach
</div>

@section('fb_pixel')
    <script>
        fbq('track', 'PageView', {}, {eventID: '{{ $this->eventId }}'});
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=1280014533998229&ev=PageView&noscript=1"/>
    </noscript>
@endsection

@section('page_scripts')
    @include('livewire.web.product.swiper-init')
@endsection