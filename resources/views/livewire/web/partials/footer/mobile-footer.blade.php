<div class="offcanvas-header border-top px-0 py-3 mt-3 d-md-none">
    <div class="nav nav-justified w-100">
        @auth
            <a class="nav-link font-neue border-end"
               href="{{ route('web.user.index') }}">
                <i class="ci-user fs-lg opacity-60 me-2"></i>
                პროფილი
            </a>
            <a class="nav-link font-neue"
               href="{{ route('web.user.index', ['page' => 'wishlist']) }}">
                <i class="ci-heart fs-lg opacity-60 me-2"></i>
                სურვილების სია
            </a>
        @else
            <a class="nav-link font-neue"
               href="#"
               aria-label="ავტორიზაცია"
               data-bs-toggle="modal"
               data-bs-target="#loginModal">
                <i class="ci-user fs-lg opacity-60 me-2"></i>
                ავტორიზაცია
            </a>
        @endauth
    </div>
</div>