<div>
    @include('livewire.web.partials.hero-slider')
    @include('livewire.web.partials.features')
    @foreach($this->productCategories->where('parent_id', 0) as $index => $category)
        @php
            $products = $category->getActiveProducts()->take(20)
        @endphp
            @include('livewire.web.partials.category-section', [
                'category' => $category,
                'products' => $products
            ])
{{--        @foreach($this->promotions->where('position', $index + 1) as $promo)--}}
{{--            @if($promo->type === 1)--}}
{{--                @include('livewire.web.partials.promo-grid', ['prom
o' => $promo])--}}
{{--            @else--}}
{{--                @include('livewire.web.partials.promo-banner', ['promo' => $promo])--}}
{{--            @endif--}}
{{--        @endforeach--}}
    @endforeach
</div>

@section('page_scripts')
    @include('livewire.web.partials.swiper-init')
@endsection