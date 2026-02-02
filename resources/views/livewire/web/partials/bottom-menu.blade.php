<nav class="navbar navbar-expand-lg navbar-light bg-white border-top fixed-bottom d-lg-none">
    <div class="container-fluid">
        <div class="nav-pills d-flex justify-content-around w-100 gap-2">	
            <a href="{{ route('web.main.index') }}" class="nav-link text-center py-3" style="flex: 1;">
                <div class="d-flex flex-column align-items-center w-100">
                    <i class="ci-home fs-5 mb-1"></i>
                </div>
            </a>
            <a href="#" class="nav-link text-center py-3" style="flex: 1;"
				data-bs-toggle="offcanvas"
				data-bs-target="#navbarNav"
				aria-controls="navbarNav"
				aria-label="Toggle navigation"
			>
                <div class="d-flex flex-column align-items-center w-100">
                    <i class="ci-menu fs-5 mb-1"></i>
                </div>
            </a>
            <button class="nav-link text-center py-3" data-bs-toggle="modal" data-bs-target="#searchModal" style="flex: 1;">
                <div class="d-flex flex-column align-items-center w-100">
                    <i class="ci-search fs-5 mb-1"></i>
                </div>
            </button>
			@auth
			<a href="{{ route('web.user.index', ['page' => 'profile']) }}" class="nav-link text-center py-3 d-flex align-items-center" style="flex: 1;">
                <div class="d-flex flex-column align-items-center">
                    <i class="ci-user fs-5 mb-1"></i>
					@if(auth()->user()->unreadNotifications->count() > 0)
						<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
							  style="font-size: 0.65rem; padding: 0.25em 0.5em;">
							{{ auth()->user()->unreadNotifications->count() }}
						</span>
					@endif
                </div>
            </a>
			@else
			<button class="nav-link text-center py-3" data-bs-toggle="modal" data-bs-target="#loginModal" style="flex: 1;">
                <div class="d-flex flex-column align-items-center w-100">
                    <i class="ci-user fs-5 mb-1"></i>
                </div>
            </button>
			@endauth
        </div>
    </div>
</nav>

<!-- ✅ Push content up to prevent covering by navbar -->
<style>
    @media (max-width: 991.98px) {
        body {
            padding-bottom: 80px;
        }	
    }

    /* ✅ Bottom Navigation Styling -->
    .navbar-expand-lg.fixed-bottom {
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
    }

    .navbar .nav-link {
        color: #6c757d !important;
        transition: all 0.3s ease;
        margin: 0.25rem;
    }

    .navbar .nav-link:hover,
    .navbar .nav-link.active {
        color: #0d6efd !important;
    }

    .navbar .nav-link small {
        font-size: 0.75rem;
        display: block;
        margin-top: 0.25rem;
    }

    .navbar .dropdown-menu {
        border: 1px solid #e9ecef;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .navbar .dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .navbar .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #0d6efd;
    }

    /* ✅ Icon colors -->
    .navbar .ci-home,
    .navbar .ci-menu,
    .navbar .ci-search,
    .navbar .ci-box,
    .navbar .ci-user {
        color: inherit;
        transition: color 0.3s ease;
    }

    .navbar .nav-link.active .ci-home,
    .navbar .nav-link.active .ci-menu,
    .navbar .nav-link.active .ci-search,
    .navbar .nav-link.active .ci-box,
    .navbar .nav-link.active .ci-user {
        color: #0d6efd;
    }
</style>