<button type="button"
        wire:click="toggle"
        class="btn {{ $size === 'sm' ? 'btn-sm' : ($size === 'lg' ? 'btn-lg' : '') }} {{ $iconOnly ? 'btn-icon' : '' }} {{ $class }}"
        wire:loading.attr="disabled"
        aria-label="{{ $isInWishlist ? 'სურვილების სიიდან წაშლა' : 'სურვილების სიაში დამატება' }}">

    <span wire:loading.remove wire:target="toggle">
        <i class="{{ $isInWishlist ? 'ci-heart-filled text-danger' : 'ci-heart' }} fs-{{ $size === 'sm' ? 'sm' : 'base' }} {{ $iconOnly ? '' : 'me-2' }} animate-target"></i>
        @if(!$iconOnly)
            <span>{{ $isInWishlist ? 'სურვილების სიიდან წაშლა' : 'სურვილების სიაში დამატება' }}</span>
        @endif
    </span>

    <span wire:loading wire:target="toggle">
        <span class="spinner-border spinner-border-sm" role="status"></span>
    </span>
</button>