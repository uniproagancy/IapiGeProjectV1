@section('og_tags')
    <meta property="og:url"
          content="{{ route('web.products.view', $product->translations->where('locale', app()->getLocale())->first()->slug ?? $product->translations->where('locale', 'ka')->first()->slug) }}"/>
    <meta property="og:type" content="article"/>
    <meta property="og:title"
          content="{{ $product->translation(app()->getLocale())->title ?? $product->translation('ka')->title }}"/>
    <meta property="og:image" content="{{ asset('storage/'.$product->main_image) }}"/>
@endsection

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
                                class="btn-secondary animate-pulse"/>
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
                        <button type="button" class="btn btn-sm btn-outline-secondary mt-4 justify-content-center"
                                id="specs-toggle-btn">
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
    <script src="https://webstatic.bog.ge/bog-sdk/bog-sdk.js?version=2&client_id=57315"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('bog:installment', (installment_data) => {
                BOG.Calculator.open({
                    bnpl: false,
                    amount: installment_data.amount,
                    onClose: () => {
                        // Modal close callback
                    },
                    onRequest: (selected, successCb, closeCb) => {
                        const {
                            amount, month, discount_code,
                        } = selected;
                        axios.post(installment_data.url, {
                            amount: amount,
                            month: month,
                            discount_code: discount_code
                        })
                            .then(function (response) {
                                successCb(response.data.orderId);
                            })
                            .catch(function (error) {
                                closeCb();
                            });
                        return false;
                    },
                    onComplete: ({redirectUrl}) => {
                        return false;
                    }
                })
            })
            Livewire.on('bog:installment-part', (part_installment_data) => {
                BOG.Calculator.open({
                    bnpl: true,
                    amount: part_installment_data.amount,
                    onClose: () => {
                        // Modal close callback
                    },
                    onRequest: (selected, successCb, closeCb) => {
                        const {
                            amount, month, discount_code,
                        } = selected;
                        axios.post(part_installment_data.url, {
                            amount: amount,
                            month: month,
                            discount_code: discount_code
                        })
                            .then(function (response) {
                                successCb(response.data.orderId);
                            })
                            .catch(function (error) {
                                closeCb();
                            });
                        return false;
                    },
                    onComplete: ({redirectUrl}) => {
                        return false;
                    }
                })
            });
        })
    </script>
    @if(Route::is('web.products.view'))
        <nav class="navbar navbar-expand navbar-dark bg-dark fixed-bottom d-lg-none bottom-nav-top"
             style="padding: 0.75rem 0; border-top: 1px solid #333; z-index: 1000;">
            <div class="container px-2">
                <div class="d-flex w-100 justify-content-between align-items-center">
                    <div>
                        <button type="button"
                                class="btn btn-lg btn-secondary w-100 animate-slide-end order-sm-2 order-md-4 font-neue"
                                style="font-size: 14px"
                                wire:click="addProduct"
                                wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="addProduct">
            <i class="ci-shopping-cart fs-lg animate-target ms-n1 me-2"></i>
        </span>
                            <span wire:loading wire:target="addProduct">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
        </span>
                        </button>
                        <button type="button"
                                class="btn btn-lg btn-outline-info w-100 animate-slide-end font-neue"
                                style="font-size: 14px"
                                @click="$wire.dispatch('openCheckoutModal', [{{ $product->id }}]); setTimeout(() => { new bootstrap.Modal(document.getElementById('checkoutModal')).show(); }, 100);">
                            სწრაფი შეძენა
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    @endif
</main>