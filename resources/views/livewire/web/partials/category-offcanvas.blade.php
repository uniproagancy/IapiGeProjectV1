<div>
    <div class="offcanvas offcanvas-start pb-sm-2 px-sm-2 p-0"
         id="categoryOffcanvas"
         tabindex="-1"
         aria-labelledby="categoryOffcanvas"
         style="width: 400px; z-index: 1060; padding: 0 !important;">
        <div class="offcanvas-header flex-column align-items-start py-3 pt-lg-4">
            <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-lg-4">
                <button type="button" class="btn-close text-white color-white" data-bs-dismiss="offcanvas" aria-label="დახურვა" style="background-color: #fff; opacity: 1 !important;"></button>
            </div>
        </div>
        <div class="offcanvas-body d-flex flex-column gap-4 pt-2 p-0">
            <ul class="w-100 rounded-top-0 rounded-bottom-4 py-1" style="list-style: none; padding: 0 !important;">
                @foreach($product_categories as $category)
                    @include('livewire.web.partials.header.category-item', ['category' => $category])
                @endforeach
            </ul>
        </div>
    </div>
    <style>
        .offcanvas {
            transition: visibility 0.3s ease, transform 0.3s ease;
        }
        .offcanvas-backdrop {
            transition: opacity 0.3s ease;
        }
    </style>
</div>