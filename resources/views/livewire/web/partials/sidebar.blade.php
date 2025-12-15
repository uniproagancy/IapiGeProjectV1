<div class="offcanvas-lg offcanvas-start pe-lg-0 pe-xl-4" id="accountSidebar">
    <div class="offcanvas-header d-lg-block py-3 p-lg-0">
        @include('livewire.web.partials.sidebar-header')
        <button type="button"
                class="btn-close d-lg-none"
                data-bs-dismiss="offcanvas"
                data-bs-target="#accountSidebar"
                aria-label="დახურვა"></button>
    </div>
    <div class="offcanvas-body d-block pt-2 pt-lg-4 pb-lg-0">
        @include('livewire.web.partials.sidebar-nav')
    </div>
</div>