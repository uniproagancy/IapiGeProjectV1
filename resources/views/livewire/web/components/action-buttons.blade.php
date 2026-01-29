<div class="d-flex align-items-center">
    <button type="button"
            class="navbar-toggler d-none navbar-stuck-show me-3"
            data-bs-toggle="collapse"
            data-bs-target="#stuckNav"
            aria-controls="stuckNav"
            aria-expanded="false"
            aria-label="Toggle navigation in navbar stuck state">
        <span class="navbar-toggler-icon"></span>
    </button>
    <button type="button"
            class="btn btn-icon btn-lg fs-xl btn-outline-secondary border-0 rounded-circle animate-shake d-lg-none"
            data-bs-toggle="collapse"
            data-bs-target="#searchBar"
            aria-expanded="false"
            aria-controls="searchBar"
            aria-label="Toggle search bar">
        <i class="ci-search animate-target"></i>
    </button>
    @auth
        <a class="btn btn-icon btn-lg fs-lg btn-outline-secondary border-0 rounded-circle animate-pulse d-none d-md-inline-flex position-relative"
           href="{{ route('web.user.index', ['page' => 'profile']) }}"
           aria-label="პროფილი">
            <i class="ci-user animate-target"></i>
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                    {{ auth()->user()->unreadNotifications->count() }}
                </span>
            @endif
        </a>
        <livewire:web.components.wishlist-counter/>
    @else
        <a class="btn btn-icon btn-lg fs-lg btn-outline-secondary border-0 rounded-circle animate-shake d-none d-md-inline-flex"
           aria-label="ავტორიზაცია"
           data-bs-toggle="modal"
           data-bs-target="#loginModal"
        >
            <i class="ci-user animate-target"></i>
        </a>
    @endauth
    <livewire:web.components.cart-counter/>
</div>