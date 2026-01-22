<section class="container pt-4 pt-md-5 pb-5 mt-sm-2 mb-2 mb-sm-3 mb-md-4 mb-lg-5">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4 g-lg-3 g-xl-4">
        @foreach($this->brands->whereNotNull('logo')->where('id', '!=', 1)->where('main', 1) as $brand)
        <div class="col">
            <a class="btn btn-outline-secondary w-100 rounded-4 p-3" href="shop-catalog-electronics.html">
                <img src="{{ asset('storage/'.$brand->logo) }}" class="d-none-dark" alt="{{ $brand->translations('locale', app()->getLocale())->first()->title ?? ''}}">
            </a>
        </div>
        @endforeach
        <div class="col">
            <a class="btn btn-outline-secondary w-100 h-100 rounded-4 p-3 font-neue" href="shop-categories-electronics.html">
                {{ trans('trans.all_brands') }}
                <i class="ci-plus-circle fs-base ms-2"></i>
            </a>
        </div>
    </div>
</section>