<header class="navbar navbar-expand-lg navbar-dark bg-dark d-block z-fixed p-0"
        data-sticky-navbar='{"offset": 500}'>
    <div class="container d-block py-1 py-lg-3" data-bs-theme="dark">
        <div class="navbar-stuck-hide pt-1"></div>
        <div class="row flex-nowrap align-items-center g-0" style="padding-bottom: 5px">
            <div class="col col-lg-3 d-flex align-items-center">
                <button type="button"
                        class="navbar-toggler me-4 me-lg-0"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#navbarNav"
                        aria-controls="navbarNav"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a href="{{ route('web.main.index') }}" class="navbar-brand me-0">
                    @include('livewire.web.partials.header.logo')
                    IAPI.GE
                </a>
            </div>
            <div class="col col-lg-9 d-flex align-items-center justify-content-end">
                @include('livewire.web.partials.header.search-desktop')
                @include('livewire.web.components.action-buttons')
            </div>
        </div>
        <div class="navbar-stuck-hide pb-1"></div>
    </div>
    @include('livewire.web.partials.header.navigation')
</header>