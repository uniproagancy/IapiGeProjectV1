<main class="content-wrapper">
    <div class="container py-5 mt-n2 mt-sm-0">
        <div class="row pt-md-2 pt-lg-3 pb-sm-2 pb-md-3 pb-lg-4 pb-xl-5">
            <aside class="col-lg-3">
                @include('livewire.web.partials.sidebar')
            </aside>
            <div class="col-lg-9">
                <div class="ps-lg-3 ps-xl-0">
                    @switch(request()->page)
                        @case('orders')
                            @include('livewire.web.user.partials.orders')
                            @break;
                        @case('wishlist')
                            @include('livewire.web.user.partials.wishlist')
                            @break;
                        @case('cart')
                            @include('livewire.web.user.partials.cart')
                            @break;
                        @case('payment')
                            {{ request()->page }}
                            @break;
                        @case('addresses')
                            {{ request()->page }}
                            @break;
                        @case('notifications')
                            {{ request()->page }}
                            @break;
                        @default
                            @include('livewire.web.user.partials.profile')
                            @break
                    @endswitch
                </div>
            </div>
        </div>
    </div>
</main>