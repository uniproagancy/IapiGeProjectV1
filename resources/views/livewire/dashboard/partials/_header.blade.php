<nav class="header-navbar navbar-expand-lg navbar navbar-fixed align-items-center navbar-shadow navbar-brand-center" data-nav="brand-center">
    <div class="navbar-header d-xl-block d-none">
        <ul class="nav navbar-nav">
            <li class="nav-item">
                <a class="navbar-brand" href="{{ route('dashboard.main') }}">
                    <h2 class="brand-text mb-0">ECOMMERCE CMS</h2>
                </a>
            </li>
        </ul>
    </div>
    <div class="navbar-container d-flex content">
        <div class="bookmark-wrapper d-flex align-items-center">
            <ul class="nav navbar-nav d-xl-none">
                <li class="nav-item">
                    <a class="nav-link menu-toggle" href="#">
                        <i class="ficon" data-feather="menu"></i>
                    </a>
                </li>
            </ul>
        </div>
        <ul class="nav navbar-nav align-items-center ms-auto">
            <li class="nav-item dropdown dropdown-notification me-25">
                <a class="nav-link" href="#" data-bs-toggle="dropdown">
                    <i class="ficon" data-feather="bell"></i>
                    <span class="badge rounded-pill bg-danger badge-up">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-media dropdown-menu-end">
                    <li class="dropdown-menu-header">
                        <div class="dropdown-header d-flex">
                            <h4 class="notification-title mb-0 me-auto">შეყტობინებები</h4>
                        </div>
                    </li>
                    <li class="scrollable-container media-list">

                    </li>
                    <li class="dropdown-menu-footer">
                        <a class="btn btn-primary w-100" href="#">სრული ჩამონათვალი</a>
                    </li>
                </ul>
            </li>
            <li class="nav-item dropdown dropdown-user">
                <a class="nav-link dropdown-toggle dropdown-user-link" id="dropdown-user" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="user-nav d-sm-flex d-none">
                        <span class="user-name fw-bolder">{{ Auth::user()->name }} {{ Auth::user()->lastname }}</span>
                        <span class="user-status">{{ Auth::user()->role->name }}</span>
                    </div>
                    <span class="avatar bg-light-success">
                        <div class="avatar-content">
                            <i data-feather="github" class="avatar-icon"></i>
                        </div>
                        <span class="avatar-status-online"></span>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-user">
                    <a class="dropdown-item" href="">
                        <i class="me-50" data-feather="user"></i> ჩემი პროფილი
                    </a>
                    <a class="dropdown-item" href="{{ route('dashboard.logout') }}">
                        <i class="me-50" data-feather="log-out"></i> გასვლა
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>