@section('seo')
    <title>ჩემი ანგარიში — IAPI.GE</title>
@endsection

@section('robots')noindex, nofollow@endsection

<main class="content-wrapper">
    <div class="container py-5 mt-n2 mt-sm-0">
        <div class="row pt-md-2 pt-lg-3 pb-sm-2 pb-md-3 pb-lg-4 pb-xl-5">
            <aside class="col-lg-3">
                @include('livewire.web.partials.sidebar')
            </aside>
            <div class="col-lg-9">
                <div class="ps-lg-3 ps-xl-0">
                    @switch($page)
                        @case('orders')
                            @include('livewire.web.user.orders')
                            @break;
                        @case('wishlist')
                            @include('livewire.web.user.wishlist')
                            @break;
                        @case('cart')
                            @include('livewire.web.user.cart')
                            @break;
                        @case('notifications')
                            @include('livewire.web.user.notifications')
                            @break;
                        @default
                            @include('livewire.web.user.profile')
                            @break
                    @endswitch
                </div>
            </div>
        </div>
    </div>
</main>