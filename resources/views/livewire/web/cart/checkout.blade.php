<main class="content-wrapper">
    <div class="container py-5">
        <div class="row pt-1 pt-sm-3 pt-lg-4 pb-2 pb-md-3 pb-lg-4 pb-xl-5">
            <div class="col-lg-9 col-xl-8 mb-5 mb-lg-0">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 font-neue">შეკვეთის გაფორმება</h1>
                </div>
                <form wire:submit.prevent="placeOrder">
                    @include('livewire.web.cart.customer-info')
                    @include('livewire.web.cart.shipping-address')
                    @include('livewire.web.cart.payment-method')
                    <div class="d-lg-none mt-4">
                        <button type="submit"
                                class="btn btn-lg btn-primary w-100 font-neue"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="placeOrder">
                                შეკვეთის დადასტურება
                                <i class="ci-chevron-right fs-lg ms-1"></i>
                            </span>
                            <span wire:loading wire:target="placeOrder">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                მუშავდება...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            @include('livewire.web.cart.order-summary')
        </div>
    </div>
</main>
@section('page_scripts')
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
@endsection
@section('fb_pixel')
    <script>
        (function() {
            function trackCheckout() {
                if (typeof fbq !== 'undefined') {
                    fbq('track', 'InitiateCheckout', {
                        value: {{ $total ?? 0 }},
                        currency: 'GEL',
                        content_type: 'product',
                        num_items: {{ $items_count ?? 0 }}
                    });
                }
            }
            document.addEventListener('livewire:navigated', trackCheckout);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', trackCheckout);
            } else {
                trackCheckout();
            }
        })();
    </script>
@endsection