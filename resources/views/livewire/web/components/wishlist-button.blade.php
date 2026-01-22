<button type="button"
        wire:click="toggle"
        class="btn btn-sm"
        wire:loading.attr="disabled"
        aria-label="{{ $isInWishlist ? 'სურვილების სიიდან წაშლა' : 'სურვილების სიაში დამატება' }}">
    <span wire:loading.remove wire:target="toggle">
        <i class="{{ $isInWishlist ? 'ci-heart-filled text-danger' : 'ci-heart' }} fs-sm animate-target"></i>
    </span>
    <span wire:loading wire:target="toggle">
        <span class="spinner-border spinner-border-sm" role="status"></span>
    </span>
</button>