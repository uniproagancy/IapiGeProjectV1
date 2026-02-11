<div>
    <button type="button"
            wire:click="addProduct"
            onclick="pixelAddToCart({{ $productId }}, {{ $productPrice }}, {{ $productTitle }})"
            class="product-card-button btn btn-icon btn-primary animate-slide-end ms-2"
            aria-label="კალათაში დამატება"
            wire:loading.attr="disabled">
        <i class="ci-shopping-cart fs-base animate-target"></i>
    </button>
</div>
