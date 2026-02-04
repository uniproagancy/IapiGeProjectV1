<div>
    <div class="offcanvas offcanvas-start pb-sm-2 px-sm-2 p-0"
         id="categoryOffcanvas"
         tabindex="-1"
         aria-labelledby="categoryOffcanvas"
         style="width: 400px; z-index: 1060; padding: 0 !important;">
        <div class="offcanvas-header flex-column align-items-start py-3 pt-lg-4">
            <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-lg-4">
                <button type="button" class="btn-close text-white color-white" data-bs-dismiss="offcanvas" aria-label="დახურვა" style="background-color: #fff; opacity: 1 !important;"></button>
            </div>
        </div>
        <div class="offcanvas-body d-flex flex-column gap-4 pt-2 p-0">
            <ul class="w-100 rounded-top-0 rounded-bottom-4 py-1" style="list-style: none; padding: 0 !important;">
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
            </ul>
        </div>
    </div>
    <style>
        .offcanvas {
            transition: visibility 0.3s ease, transform 0.3s ease;
        }
        .offcanvas-backdrop {
            transition: opacity 0.3s ease;
        }
    </style>
    <style>
        .category-item:hover a {
            color: #ff6900;
        }
        .category-link { color: #252525; text-decoration: none}
    </style>
</div>