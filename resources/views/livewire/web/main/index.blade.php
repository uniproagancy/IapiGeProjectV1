@section('seo')
    <title>IAPI.GE — შეიძინე იაფად | iapi.ge</title>
    <meta name="keywords"
          content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

<div>
    @include('livewire.web.partials.content.hero-slider')
    @include('livewire.web.partials.features')
    @include('livewire.web.partials.sections-carousel', ['sections' => $homeSections, 'heading' => 'საზაფხულო შემოთავაზება'])

    @foreach($this->promotions->where('position', 1) as $promo)
        @if($promo->type === 1)
            @include('livewire.web.product.promo-grid', ['promo' => $promo])
        @endif
    @endforeach

    @php $catIndex = 0; @endphp
    @foreach($this->productCategories->where('parent_id', 0) as $category)

        {{-- ბრენდები შუაში --}}
        @if(count($this->productCategories->where('parent_id', 0)) / 2 === $catIndex)
            <div style="background: #ff6900; padding: 0 0 25px;">
                <section class="container py-4 mt-sm-3 mt-lg-5">
                    <div class="position-relative">
                        <div class="product-swiper overflow-hidden" data-section="brand-{{ $catIndex }}">
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
                        <div class="swiper-prev" data-section="brand-{{ $catIndex }}"><i class="ci-chevron-left"></i></div>
                        <div class="swiper-next" data-section="brand-{{ $catIndex }}"><i class="ci-chevron-right"></i></div>
                    </div>
                </section>
            </div>
        @endif
        @php $products = $category->getActiveProducts()->take(20) @endphp
        @include('livewire.web.partials.category-section', [
            'category' => $category,
            'products' => $products,
        ])
        @if($catIndex === 2)
            <div class="container pt-5 mt-2 mt-sm-3 mt-lg-4">
                <a href="/products/auzebi-183" class="d-block">
                    <img src="{{ asset('web-assets/banners/banner_1.png') }}"
                         alt="ბანერი"
                         class="w-100 rounded-4"
                         style="object-fit: cover; max-height: 200px;">
                </a>
            </div>
        @endif

        @if($catIndex === 10)
            <div class="container pt-5 mt-2 mt-sm-3 mt-lg-4">
                <a href="/products/konditsioneri-32" class="d-block">
                    <img src="{{ asset('web-assets/banners/banner_2.png') }}"
                         alt="ბანერი"
                         class="w-100 rounded-4"
                         style="object-fit: cover; max-height: 200px;">
                </a>
            </div>
        @endif
        @php $catIndex++ @endphp
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