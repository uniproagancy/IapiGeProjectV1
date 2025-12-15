<a class="btn btn-icon btn-lg fs-lg btn-outline-secondary border-0 rounded-circle animate-pulse d-none d-md-inline-flex position-relative"
   href="{{ route('web.user.index', ['page' => 'wishlist']) }}"
   wire:navigate
   aria-label="სურვილების სია">
    <i class="ci-heart animate-target"></i>
    @if($wishlistCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              style="font-size: 0.65rem; padding: 0.25em 0.5em;">
            {{ $wishlistCount }}
        </span>
    @endif
</a>