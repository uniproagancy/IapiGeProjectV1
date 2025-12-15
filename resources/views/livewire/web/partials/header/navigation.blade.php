<div class="collapse navbar-stuck-hide" id="stuckNav">
    <nav class="offcanvas offcanvas-start" id="navbarNav" tabindex="-1" aria-labelledby="navbarNavLabel"
         style="z-index: 1050;">
        <div class="offcanvas-header py-3">
            <h5 class="offcanvas-title" id="navbarNavLabel">IAPI.GE</h5>
            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
        </div>
        <div class="offcanvas-body py-3 py-lg-0">
            <div class="container px-0 px-lg-3">
                <div class="row">
                    <div class="col-lg-3">
                        @include('livewire.web.partials.categories-dropdown')
                    </div>
                    <div class="col-lg-9 d-lg-flex pt-3 pt-lg-0 ps-lg-0">
                        @include('livewire.web.partials.main-menu')
                    </div>
                </div>
            </div>
        </div>
        @include('livewire.web.partials.footer.mobile-footer')
    </nav>
</div>