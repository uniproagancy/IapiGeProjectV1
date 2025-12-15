<li class="dropend position-static">
    <div class="position-relative rounded pt-2 pb-1 px-lg-2"
         data-bs-toggle="dropdown"
         data-bs-trigger="hover">
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
        <div class="dropdown-item fw-medium text-wrap stretched-link d-lg-none font-neue" style="font-size: 13px">
            <img src="{{ asset('web-assets/icons/categories/'.$category->id.'.svg') }}" width="25">
            {{ $category->translation(app()->getLocale())->title ?? $category->translation('ka')->title }}
            @if($category->children->where('active', 1)->where('show', 1)->count() > 0)
                <i class="ci-chevron-down fs-base ms-auto me-n1"></i>
            @endif
        </div>
    </div>
    @if($category->children->where('active', 1)->where('show', 1)->count() > 0)
        <div class="dropdown-menu rounded-4 p-4"
             style="top: 1rem; height: calc(100% - .1875rem); --cz-dropdown-spacer: .3125rem; animation: none;">
            <div class="d-flex flex-column flex-lg-row h-100 gap-4">
                <div style="min-width: 194px">
                    <ul class="nav flex-column gap-2 mt-n2">
                        @foreach($category->children->where('active', 1)->where('show', 1) as $childCategory)
                            <li class="d-flex w-100 pt-1">
                                <a class="nav-link animate-underline animate-target d-inline fw-normal text-truncate p-0"
                                   href="{{ route('web.products.index', $childCategory->translation(app()->getLocale())->slug ?? $childCategory->translation('ka')->slug) }}">
                                    {{ $childCategory->translation(app()->getLocale())->title ?? $childCategory->translation('ka')->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
</li>