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
        <a class="btn btn-icon btn-lg fs-lg btn-outline-secondary border-0 rounded-circle animate-shake d-none d-md-inline-flex"
           href="{{ route('web.user.index') }}"
           aria-label="პროფილი">
            <i class="ci-user animate-target"></i>
        </a>
        <livewire:web.components.wishlist-counter />
    @else
        <a class="btn btn-icon btn-lg fs-lg btn-outline-secondary border-0 rounded-circle animate-shake d-none d-md-inline-flex"
           aria-label="ავტორიზაცია"
           data-bs-toggle="modal"
           data-bs-target="#loginModal"
        >
            <i class="ci-user animate-target"></i>
        </a>
    @endauth
    <livewire:web.cart.counter />
</div>