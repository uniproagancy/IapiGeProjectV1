<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0 font-neue">{{ __('trans.notifications') }}</h2>
        <div class="d-flex gap-2">
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="btn btn-sm btn-outline-primary font-neue">
                    {{ __('trans.mark_all_read') }}
                </button>
            @endif
            @if($notifications->count() > 0)
                <button wire:click="deleteAll"
                        wire:confirm="{{ __('trans.delete_all_confirm') }}"
                        class="btn btn-sm btn-outline-danger font-neue">
                    {{ __('trans.delete_all') }}
                </button>
            @endif
        </div>
    </div>
    <ul class="nav nav-tabs mb-4 font-neue">
        <li class="nav-item">
            <button wire:click="setFilter('all')"
                    class="nav-link {{ $filter === 'all' ? 'active' : '' }}">
                {{ __('trans.all') }}
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setFilter('unread')"
                    class="nav-link {{ $filter === 'unread' ? 'active' : '' }}">
                {{ __('trans.unread') }}
                @if($unreadCount > 0)
                    <span class="badge bg-primary ms-1">{{ $unreadCount }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item">
            <button wire:click="setFilter('read')"
                    class="nav-link {{ $filter === 'read' ? 'active' : '' }}">
                {{ __('trans.read') }}
            </button>
        </li>
    </ul>
    @if($notifications->count() > 0)
        <div class="row row-cols-1 g-3">
            @foreach($notifications as $notification)
            <div class="d-md-flex align-items-center justify-content-between gap-4 border-bottom py-3">
                <div class="d-flex fs-sm pt-2 pt-md-0 ps-3 ps-md-0 mb-2 mb-md-0 align-items-center">
                    <span class="d-flex align-items-center fs-sm fw-medium text-body-emphasis px-1">
                        <span class="btn btn-outline-{{ $notification->data['color'] ?? 'primary' }} btn-sm font-neue ">
                            <span class="bg-{{ $notification->data['color'] ?? 'primary' }} rounded-circle p-1 me-2"></span>
                            {{ $notification->data['title'] }}
                        </span>
                    </span>
                    <span class="mx-1">{{ $notification->data['message'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4">
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="ci-bell fs-1 text-muted mb-3 d-block"></i>
                <h5 class="font-neue">{{ __('trans.no_notifications') }}</h5>
                <p class="text-muted mb-0">{{ __('trans.no_notifications_desc') }}</p>
            </div>
        </div>
    @endif
</div>