<div>
    <button type="button"
            wire:click="addProduct"
            onclick="pixelAddToCart(
                {{ $productId }},
                {{ $productPrice }},
                @js($productTitle),
                @js($cartEventId),
            )"
            class="product-card-button btn btn-icon btn-primary animate-slide-end ms-2"
            aria-label="კალათაში დამატება"
            data-url="{{ url()->current() }}"
            wire:loading.attr="disabled">
        <i class="ci-shopping-cart fs-base animate-target"></i>
    </button>
</div>
<script>
    Livewire.on('fb-add-to-cart', (data) => {
        fbq('track', 'AddToCart', {
            content_ids: [String(data.id)],
            content_type: 'product',
            content_name: data.name,
            value: data.price,
            currency: 'GEL',
            contents: [{ id: String(data.id), quantity: data.quantity }]
        }, {
            eventID: data.eventId
        });
    });
</script>