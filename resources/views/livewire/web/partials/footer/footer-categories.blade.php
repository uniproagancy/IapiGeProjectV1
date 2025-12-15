<div class="d-flex flex-column gap-3 pb-3 pb-md-4 pb-lg-5 mt-n2 mt-sm-n4 mt-lg-0 mb-4">
    <ul class="nav align-items-center text-body-tertiary gap-2 font-neue flex-wrap justify-content-center">
        @foreach($product_categories->where('show_on_main', 1) as $index => $category)
            <li class="animate-underline text-center">
                <a class="nav-link fw-normal p-0 animate-target"
                   href="{{ route('web.products.index', $category->translation(app()->getLocale())->slug ?? $category->translation('ka')->slug) }}">
                    {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
                </a>
            </li>

            @if(!$loop->last)
                <li class="px-1">/</li>
            @endif
        @endforeach
    </ul>
</div>