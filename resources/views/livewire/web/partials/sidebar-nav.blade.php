@php
    $menuItems = [
        [
            'title' => 'პირადი ინფორმაცია',
            'icon' => 'ci-user',
            'route' => 'web.user.index',
            'page' => ''
        ],
        [
            'title' => 'შეკვეთები',
            'icon' => 'ci-shopping-bag',
            'route' => 'web.user.index',
            'page' => 'orders',
        ],
        [
            'title' => 'სურვილების სია',
            'icon' => 'ci-heart',
            'route' => 'web.user.index',
            'page' => 'wishlist'
        ],
        [
            'title' => 'ჩემი კალათა',
            'icon' => 'ci-shopping-cart',
            'route' => 'web.user.index',
            'page' => 'cart'
        ],
        [
            'title' => 'შეტყობინებები',
            'icon' => 'ci-bell',
            'route' => 'web.user.index',
            'page' => 'notifications',
            'badge' => auth()->user()->unreadNotifications->count()
        ],
    ];
@endphp

<nav class="list-group list-group-borderless">
    @foreach($menuItems as $item)
        <a class="list-group-item list-group-item-action d-flex align-items-center {{ request()->segment(2) === $item['page'] ? 'active' : '' }}"
           href="{{ route($item['route'], $item['page']) }}">
            <i class="{{ $item['icon'] }} fs-base opacity-75 me-2"></i>
            {{ $item['title'] }}
            @if(isset($item['badge']) && $item['badge'] > 0)
                <span class="badge bg-primary rounded-pill ms-auto">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endforeach
</nav>
<nav class="list-group list-group-borderless pt-3">
    <form method="POST" action="">
        @csrf
        <a href="{{ route('web.logout') }}" class="list-group-item list-group-item-action d-flex align-items-center border-0 bg-transparent w-100 text-start">
            <i class="ci-log-out fs-base opacity-75 me-2"></i>
            გასვლა
        </a>
    </form>
</nav>