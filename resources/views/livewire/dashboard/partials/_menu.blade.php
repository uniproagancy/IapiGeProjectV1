<div class="horizontal-menu-wrapper">
    <div class="header-navbar navbar-expand-sm navbar navbar-horizontal floating-nav navbar-dark navbar-shadow menu-border container-fluid" role="navigation" data-menu="menu-wrapper" data-menu-type="floating-nav">
        <div class="navbar-header">
            <ul class="nav navbar-nav flex-row">
                <li class="nav-item me-auto">
                    <a class="navbar-brand" href="{{ route('dashboard.main') }}">
                        <h2 class="brand-text mb-0">ECOMMERCE CMS</h2>
                    </a>
                </li>
                <li class="nav-item nav-toggle">
                    <a class="nav-link modern-nav-toggle pe-0" data-bs-toggle="collapse">
                        <i class="d-block d-xl-none text-primary toggle-icon font-medium-4" data-feather="x"></i>
                    </a>
                </li>
            </ul>
        </div>
        <div class="shadow-bottom"></div>
        <div class="navbar-container main-menu-content" data-menu="menu-container">
            <ul class="nav navbar-nav" id="main-menu-navigation" data-menu="menu-navigation">
                <li class="dropdown nav-item @if(empty(request()->segment(2))) active @endif">
                    <a class="nav-link d-flex align-items-center" href="{{ route('dashboard.main') }}">
                        <i data-feather="home"></i>
                        <span>მთავარი გვერდი</span>
                    </a>
                </li>
                <li class="dropdown nav-item @if(request()->segment(2) === 'users') active @endif">
                    <a class="nav-link d-flex align-items-center" href="{{ route('dashboard.user.index') }}">
                        <i data-feather="users"></i>
                        <span>მომხმარებლები</span>
                    </a>
                </li>
                <li class="dropdown nav-item @if(request()->segment(2) === "companies") active @endif">
                    <a class="nav-link d-flex align-items-center" href="{{ route('dashboard.company.index') }}">
                        <i data-feather="file"></i>
                        <span>კომპანიები</span>
                    </a>
                </li>
                <li class="dropdown nav-item @if(request()->segment(2) === "products") active @endif" data-menu="dropdown">
                    <a class="dropdown-toggle nav-link d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                        <i data-feather="package"></i>
                        <span>პროდუქცია</span>
                    </a>
                    <ul class="dropdown-menu" data-bs-popper="none">
                        <li data-menu="">
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('dashboard.product.category.index') }}" data-bs-toggle="">
                                <i data-feather="circle"></i>
                                <span>კატეგორიები</span>
                            </a>
                        </li>
                        <li data-menu="">
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('dashboard.product.brand.index') }}" data-bs-toggle="">
                                <i data-feather="circle"></i>
                                <span>ბრენდები</span>
                            </a>
                        </li>
                        <li data-menu="">
                            <a class="dropdown-item d-flex align-items-center" href="{{ route('dashboard.product.index') }}" data-bs-toggle="">
                                <i data-feather="circle"></i>
                                <span>ჩამონათვალი</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="dropdown nav-item @if(request()->segment(2) === "orders") active @endif">
                    <a class="nav-link d-flex align-items-center" href="{{ route('dashboard.order.index') }}">
                        <i data-feather="shopping-cart"></i>
                        <span>შეკვეთები</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>