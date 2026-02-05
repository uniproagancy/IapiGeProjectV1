<div>
    <!-- ✅ Shopping Cart Offcanvas - Fixed Modal Sequence -->
    <div class="offcanvas offcanvas-end pb-sm-2 px-sm-2"
         id="shoppingCart"
         tabindex="-1"
         aria-labelledby="shoppingCartLabel"
         style="width: 500px; z-index: 1060;"
         @if($cartItems->count() > 0) wire:ignore.self @endif>

        <div class="offcanvas-header flex-column align-items-start py-3 pt-lg-4">
            <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-lg-4">
                <h4 class="offcanvas-title font-neue" id="shoppingCartLabel">კალათა</h4>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="დახურვა"></button>
            </div>
        </div>

        <div class="offcanvas-body d-flex flex-column gap-4 pt-2">
            @forelse($cartItems as $item)
                <div class="d-flex align-items-center" wire:key="cart-item-{{ $item->id }}">
                    <a class="position-relative flex-shrink-0"
                       href="{{ route('web.products.view', $item->attributes->slug) }}"
                       data-bs-dismiss="offcanvas">
                        @if($item->attributes->discount_percent)
                            <span class="badge text-bg-danger position-absolute top-0 start-0">
                            -{{ $item->attributes->discount_percent }}%
                        </span>
                        @endif
                        <img src="{{ asset('storage/' . $item->attributes->image) }}"
                             width="110"
                             alt="{{ $item->name }}"
                             loading="lazy">
                    </a>
                    <div class="w-100 min-w-0 ps-2 ps-sm-3">
                        <h5 class="d-flex animate-underline mb-2">
                            <a class="d-block fs-sm fw-medium text-truncate animate-target"
                               href="{{ route('web.products.view', $item->attributes->slug) }}"
                               data-bs-dismiss="offcanvas">
                                {{ $item->name }}
                            </a>
                        </h5>
                        <div class="h6 pb-1 mb-2">
                            {{ number_format($item->price, 2) }} ₾
                            @if($item->attributes->discount_percent)
                                <del class="text-body-tertiary fs-xs fw-normal">
                                    {{ number_format($item->attributes->regular_price, 2) }} ₾
                                </del>
                            @endif
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="countInput rounded-2">
                                <button type="button"
                                        class="btn btn-icon btn-sm"
                                        wire:click="decrementQuantity('{{ $item->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="decrementQuantity"
                                        aria-label="რაოდენობის შემცირება"
                                        {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                    <i class="ci-minus"></i>
                                </button>
                                <input type="number"
                                       class="form-control form-control-sm"
                                       value="{{ $item->quantity }}"
                                       readonly>
                                <button type="button"
                                        class="btn btn-icon btn-sm"
                                        wire:click="incrementQuantity('{{ $item->id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="incrementQuantity"
                                        aria-label="რაოდენობის გაზრდა">
                                    <i class="ci-plus"></i>
                                </button>
                            </div>
                            <button type="button"
                                    class="btn-close fs-sm"
                                    wire:click="removeItem('{{ $item->id }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="removeItem"
                                    aria-label="წაშლა"></button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="ci-shopping-cart fs-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted">თქვენი კალათა ცარიელია</p>
                    <a href="{{ route('web.products.index') }}"
                       class="btn btn-primary font-neue"
                       data-bs-dismiss="offcanvas">
                        დაიწყეთ შოპინგი
                    </a>
                </div>
            @endforelse
        </div>

        @if($cartItems->count() > 0)
            <div class="offcanvas-header flex-column align-items-start">
                <div class="d-flex align-items-center justify-content-between w-100 mb-3 mb-md-4">
                    <span class="text-light-emphasis">ჯამი:</span>
                    <span class="h6 mb-0">{{ number_format($cartSubTotal, 2) }} ₾</span>
                </div>
                <div class="d-flex w-100 gap-3">
                    @auth
                        <!-- ✅ User is logged in - show checkout links -->
                        <a class="btn btn-lg btn-secondary w-100 font-neue"
                           href="{{ route('web.user.index', ['page' => 'cart']) }}">
                            კალათის ნახვა
                        </a>
                        <a class="btn btn-lg btn-primary w-100 font-neue"
                           href="{{ route('web.checkout.index') }}">
                            შეკვეთა
                        </a>
                    @else
                        <button type="button"
                                class="btn btn-lg btn-primary w-100 font-neue"
                                id="checkoutBtn">
                            შეკვეთა
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>
    <script>
        document.addEventListener('livewire:initialized', () => {
            // ✅ Get checkout button
            const checkoutBtn = document.getElementById('checkoutBtn');

            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    console.log('✅ Checkout clicked - closing cart first...');

                    // ✅ Step 1: Close shopping cart
                    const cartOffcanvas = document.getElementById('shoppingCart');
                    const offcanvas = bootstrap.Offcanvas.getInstance(cartOffcanvas);

                    if (offcanvas) {
                        // ✅ Step 2: After cart closes, show login modal
                        offcanvas.hide();

                        // ✅ Wait for offcanvas to close (300ms animation)
                        setTimeout(() => {
                            console.log('✅ Cart closed - opening login modal...');
                            const loginModal = new bootstrap.Modal(
                                document.getElementById('loginModal'),
                                { backdrop: 'static', keyboard: false }
                            );
                            loginModal.show();
                        }, 300);  // Bootstrap offcanvas animation duration
                    }
                });
            }
        });

        // ✅ Alternative: Livewire event listener approach
        // Uncomment if you want to use Livewire events instead
        /*
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('showLoginModal', () => {
                const cartOffcanvas = document.getElementById('shoppingCart');
                const offcanvas = bootstrap.Offcanvas.getInstance(cartOffcanvas);

                if (offcanvas) {
                    offcanvas.hide();

                    setTimeout(() => {
                        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                        loginModal.show();
                    }, 300);
                }
            });
        });
        */

        // ✅ Keep the existing offcanvas update behavior
        document.addEventListener('livewire:updated', function () {
            const offcanvasElement = document.getElementById('shoppingCart');
            const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);

            if (offcanvas) {
                offcanvas.show();
            }
        });
    </script>

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