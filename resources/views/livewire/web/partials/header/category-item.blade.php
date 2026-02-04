<li class="position-static border-bottom category-item">
    <div class="position-relative rounded pt-2 pb-2 px-4">
        <a class="dropdown-item fw-medium stretched-link d-none d-lg-flex font-neue"
           href="{{ route('web.products.index', $category->translation(app()->getLocale())->slug ?? $category->translation('ka')->slug) }}">
            <img src="{{ asset('web-assets/icons/categories/'.$category->id.'.svg') }}" width="25">
            <span class="text-truncate" style="font-size: 13px; padding-left: 5px">
                {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
            </span>
            @if($category->children->where('active', 1)->where('show', 1)->count() > 0)
                <i class="ci-chevron-right fs-base ms-auto me-n1"></i>
            @endif
        </a>
        <a href="{{ route('web.products.index', $category->translation(app()->getLocale())->slug ?? $category->translation('ka')->slug) }}"
           class="fw-medium text-wrap stretched-link d-lg-none font-neue category-link" style="font-size: 13px">
            <img src="{{ asset('web-assets/icons/categories/'.$category->id.'.svg') }}" width="25">
            {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
        </a>
    </div>
</li>
<style>
    .category-item:hover a {
        color: #ff6900;
    }
    .category-link { color: #252525; text-decoration: none}
</style>