<div class="navbar-nav">
    <div class="dropdown w-100">
        <div class="cursor-pointer d-none d-lg-block"
             data-bs-toggle="dropdown"
             data-bs-trigger="hover"
             data-bs-theme="dark">
            <a class="position-absolute top-0 start-0 w-100 h-100" href="{{ route('web.products.index') }}">
                <span class="visually-hidden font-neue">{{ trans('trans.categories') }}</span>
            </a>
            <button type="button"
                    class="btn btn-lg btn-secondary dropdown-toggle w-100 rounded-bottom-0 justify-content-start pe-none">
                <i class="ci-grid fs-lg"></i>
                <span class="ms-2 me-auto font-neue">{{ trans('trans.categories') }}</span>
            </button>
        </div>
        <button type="button"
                class="btn btn-lg btn-secondary dropdown-toggle w-100 justify-content-start d-lg-none mb-2"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside">
            <i class="ci-grid fs-lg"></i>
            <span class="ms-2 me-auto font-neue" style="font-size: 13px">{{ trans('trans.categories') }}</span>
        </button>
        <ul class="dropdown-menu w-100 rounded-top-0 rounded-bottom-4 py-1 p-lg-1"
            style="--cz-dropdown-spacer: 0; --cz-dropdown-item-padding-y: .625rem; --cz-dropdown-item-spacer: 0">
            <li class="d-lg-none pt-2">
                <a class="dropdown-item fw-medium font-neue"
                   href="{{ route('web.products.index') }}">
                    <i class="ci-grid fs-xl opacity-60 pe-1 me-2"></i>
                    ყველა კატეგორია
                    <i class="ci-chevron-right fs-base ms-auto me-n1"></i>
                </a>
            </li>
            @foreach($product_categories as $category)
                @include('livewire.web.partials.header.category-item', ['category' => $category])
            @endforeach
        </ul>
    </div>
</div>