<div>
    <div class="offcanvas offcanvas-end pb-sm-2 px-sm-2"
         id="searchOffcanvas"
         tabindex="-1"
         aria-labelledby="searchOffcanvas"
         style="width: 500px; z-index: 1060; background-color: rgba(0,0,0,0.05)">
        <div class="offcanvas-header flex-column align-items-start py-3 pt-lg-4">
            <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-lg-4">
                <button type="button" class="btn-close text-white color-white" data-bs-dismiss="offcanvas" aria-label="დახურვა" style="background-color: #fff; opacity: 1 !important;"></button>
            </div>
        </div>
        <div class="offcanvas-body d-flex flex-column gap-4 pt-2">
            <livewire:web.search.live-search/>
        </div>
    </div>
    <style>
        /* ✅ Optional: Add smooth transition to cart closing */
        .offcanvas {
            transition: visibility 0.3s ease, transform 0.3s ease;
        }
        .offcanvas-backdrop {
            transition: opacity 0.3s ease;
        }
    </style>
</div>