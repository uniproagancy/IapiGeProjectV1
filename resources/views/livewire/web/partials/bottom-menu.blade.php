<nav class="navbar navbar-expand navbar-dark bg-dark fixed-bottom d-lg-none bottom-nav"
     style="padding: 0.75rem 0; border-top: 1px solid #333; z-index: 1000;">
    <div class="container-fluid px-0">
        <div class="d-flex w-100 justify-content-around align-items-center">
            <a class="nav-link-custom text-center text-white-50"
               href="{{ route('web.main.index') }}"
               title="მთავარი">
                <i class="ci-home fs-5 mb-1"></i>
                <div class="nav-label font-neue">მთავარი</div>
            </a>
            <button type="button"
                    class="nav-link-custom text-center text-white-50 border-0 bg-transparent"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#navbarNav"
                    aria-controls="navbarNav"
                    aria-label="Toggle navigation"
                    title="ნავიგაცია">
                <i class="ci-menu fs-5 mb-1"></i>
                <div class="nav-label font-neue">ნავიგაცია</div>
            </button>
            <button type="button"
                    class="nav-link-custom text-center text-white-50 border-0 bg-transparent position-relative"
                    wire:click="openCheckoutModal"
                    title="კალათა">
                <i class="ci-search fs-5 mb-1"></i>
                <div class="nav-label font-neue">ძებნა</div>
            </button>
            @if(Auth::check())
            <a class="nav-link-custom text-center text-white-50"
               href="{{ route('web.user.index') }}"
               title="პროფილი">
                <i class="ci-user fs-5 mb-1"></i>
                <div class="nav-label font-neue">პროფილი</div>
            </a>
            @else
            <a class="nav-link-custom text-center text-white-50"
               href="#"
               aria-label="ავტორიზაცია"
               data-bs-toggle="modal"
               data-bs-target="#loginModal"
               title="პროფილი">
                <i class="ci-user fs-5 mb-1"></i>
                <div class="nav-label font-neue">პროფილი</div>
            </a>
            @endif
        </div>
    </div>
</nav>
<style>
    .bottom-nav-top {
        border-top: 2px solid #ff6900 !important;
        background: linear-gradient(to right, #ff6900 , #ff6900 ) !important;
        padding: 8px 0 !important;
        box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
        bottom: 60px;
    }

    .bottom-nav {
        border-top: 2px solid #ff6900 !important;
        background: linear-gradient(to right, #1a1a1a, #222) !important;
        padding: 8px 0 !important;
        box-shadow: 0 -2px 15px rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
    }
    .nav-link-custom {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4px 8px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        color: rgba(255, 255, 255, 0.6) !important;
        position: relative;
        min-height: 60px;
    }

    .nav-link-custom:hover {
        color: #fff !important;
        transform: translateY(-4px);
    }

    .nav-link-custom:active {
        transform: translateY(-2px);
    }

    /* ✅ Icon Styling -->
    .nav-link-custom i {
        transition: all 0.3s ease;
        color: inherit;
        display: block;
    }

    .nav-link-custom:hover i {
        color: #ff5722 !important;
        transform: scale(1.15);
    }

    /* ✅ Label Text */
    .nav-label {
        font-size: 10px;
        font-weight: 500;
        margin-top: 2px;
        color: inherit;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* ✅ Badge Styling -->
    .nav-badge {
        position: absolute;
        top: 2px;
        right: 4px;
        background-color: #ff5722;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        box-shadow: 0 2px 5px rgba(255, 87, 34, 0.4);
        animation: badge-pulse 2s ease-in-out infinite;
    }

    @keyframes badge-pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 2px 5px rgba(255, 87, 34, 0.4);
        }
        50% {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(255, 87, 34, 0.6);
        }
    }

    /* ✅ Discount Circle (Center) */
    .discount-circle {
        width: 52px;
        height: 52px;
        background: #ff5722;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 -3px 10px rgba(255, 87, 34, 0.3);
        margin: 0 auto -8px;
        transition: all 0.3s ease;
        animation: float 3s ease-in-out infinite;
    }

    .discount-btn:hover .discount-circle {
        transform: translateY(-4px);
        box-shadow: 0 -4px 15px rgba(255, 87, 34, 0.4);
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-3px);
        }
    }

    /* ✅ Page padding to accommodate fixed navbar */
    body {
        padding-bottom: 70px;
    }

    /* ✅ Hide on desktop -->
    @media (min-width: 992px) {
        .bottom-nav {
            display: none !important;
        }

        body {
            padding-bottom: 0;
        }
    }

    /* ✅ Responsive adjustments for small screens */
    @media (max-width: 360px) {
        .nav-label {
            font-size: 9px;
        }

        .nav-link-custom {
            min-height: 55px;
            padding: 2px 6px;
        }

        .nav-link-custom i {
            font-size: 1.25rem !important;
        }

        .discount-circle {
            width: 46px;
            height: 46px;
        }
    }
</style>

<!-- ✅ Livewire Event Listeners -->
<script>
    document.addEventListener('livewire:initialized', () => {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-link-custom').forEach(link => {
            if (link.getAttribute('href') === currentPath) {
                link.classList.add('active');
                link.style.color = '#ff5722 !important';
            }
        });
    });
</script>