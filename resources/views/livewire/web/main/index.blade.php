@section('seo')
    <title>IAPI.GE - იაფი მაღაზია</title>
    <meta name="keywords"
          content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
@endsection

<div style="">
    @include('livewire.web.partials.content.hero-slider')
    @include('livewire.web.partials.features')
    @foreach($this->productCategories->where('parent_id', 0) as $index => $category)
        @foreach($this->promotions->where('position', $index + 1) as $promo)
            @if($promo->type === 1)
                @include('livewire.web.product.promo-grid', ['promo' => $promo])
            @else
                @include('livewire.web.product.promo-banner', ['promo' => $promo])
            @endif
        @endforeach
        @if(count($this->productCategories->where('parent_id', 0)) / 2 === $index)
            <div style="background: #ff6900;padding: 0 0 25px;">
                <section class="container py-4 mt-sm-3 mt-lg-5">
                    <div class="position-relative">
                        <div class="product-swiper overflow-hidden" data-section="brand-{{$index}}">
                            <div class="swiper-wrapper">
                                @foreach($this->brands as $brand)
                                    @if(file_exists(public_path('web-assets/brands/'.$brand->id.'.svg')))
                                        <div class="swiper-slide" wire:key="brand-item-{{ $brand->id }}">
                                            <a class="btn btn-outline-secondary w-100 rounded-4 p-3"
                                               href="{{ route('web.products.index', ['brands[0]' => $brand->id]) }}"
                                               style="height: 100px; background: #fff;">
                                                <img src="{{ asset('web-assets/brands/'.$brand->id.'.svg') }}"
                                                     class="d-none-dark" alt="Apple" style="height: 80px;">
                                            </a>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="swiper-prev" data-section="brand-{{$index}}">
                            <i class="ci-chevron-left"></i>
                        </div>
                        <div class="swiper-next" data-section="brand-{{$index}}">
                            <i class="ci-chevron-right"></i>
                        </div>
                    </div>
                </section>
            </div>
        @endif
        @php
            $products = $category->getActiveProducts()->take(20)
        @endphp
        @include('livewire.web.partials.category-section', [
            'category' => $category,
            'products' => $products
        ])
    @endforeach
</div>
@section('fb_pixel')
    <script>
        fbq('track', 'PageView', {}, { eventID: '{{ $this->eventId }}' });
    </script>
    <noscript><img height="1" width="1" style="display:none"
                   src="https://www.facebook.com/tr?id=1280014533998229&ev=PageView&noscript=1"
        /></noscript>
@endsection
@section('page_scripts')
    @include('livewire.web.product.swiper-init')
@endsection