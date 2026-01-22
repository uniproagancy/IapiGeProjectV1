<main class="content-wrapper">
    @include('livewire.web.product.breadcrumb')
    <section class="container pb-5 mb-1 mb-sm-2 mb-md-3 mb-lg-4 mb-xl-5">
        <div class="row">
            <div class="col-md-10 col-xl-8 pt-1">
                <div class="row">
                    <div class="d-flex justify-content-between">
                        <h1 class="h3 mb-1 font-neue">
                            {{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}
                        </h1>
                        <livewire:web.components.wishlist-button
                                :productId="$product->id"
                                class="btn-secondary animate-pulse" />
                    </div>
                    <div class="col-md-8">
                        <span class="font-neue" style="font-size: 14px">SKU: {{ $product->sku }}</span>
                        @include('livewire.web.product.gallery')
                    </div>
                    <div class="col-md-4">
                        @include('livewire.web.product.specs')
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-xl-4 pt-1">
                @include('livewire.web.product.purchase-section')
            </div>
            @if(!empty($product->fullSpecifications))
            <div class="col-12">
                <div class="rounded collapsed" id="specification-section" style="padding: 15px; margin-top: 25px">
                <div id="specs-wrapper" class="specs-collapsed masonry-grid">
                    @foreach($product->fullSpecifications as $full_specification_item)
                        <div class="masonry-item p-1 rounded mb-3">
                            <h3 class="h6 mb-3 font-neue">{{ $full_specification_item->name }}</h3>
                            <ul class="list-unstyled d-flex flex-column gap-2 fs-sm m-0">
                                @foreach($full_specification_item->list as $list_item)
                                <li class="d-flex align-items-center position-relative pe-4">
                                    <span>{{ $list_item->name }}:</span>
                                    <span class="d-block flex-grow-1 border-bottom border-dashed mx-2"></span>
                                    <span class="text-dark-emphasis fw-medium">{{ $list_item->value }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-4 justify-content-center" id="specs-toggle-btn">
                        სრული მახასიათებლები
                        <i class="ci-chevron-down ms-1"></i>
                    </button>
                </div>
            </div>
            @endif
            <div class="col-12">
                @include('livewire.web.product.similar-products')
                @include('livewire.web.partials.installment-modal')
                <livewire:web.product.checkout-modal>
            </div>
        </div>
    </section>
    <style>
        #specification-section {
            overflow: hidden;
        }

        #specification-section.expanded {
            max-height: 2000px !important;
        }

        #specification-section.collapsed {
            max-height: 280px;
        }

        .masonry-grid {
            column-count: 2;
            overflow: hidden;
        }

        .masonry-item {
            display: inline-block;
            width: 100%;
            margin-bottom: 20px;
            break-inside: avoid;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const wrapper = document.getElementById("specification-section");
            const btn = document.getElementById("specs-toggle-btn");
            btn.addEventListener("click", function () {
                wrapper.classList.toggle("expanded");
                if (wrapper.classList.contains("expanded")) {
                    btn.innerHTML = 'დამალვა <i class="ci-chevron-up ms-1"></i>';
                } else {
                    btn.innerHTML = 'სრული მახასიათებლები <i class="ci-chevron-down ms-1"></i>';
                }
            });
        });
    </script>
</main>