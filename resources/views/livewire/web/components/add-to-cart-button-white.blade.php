<div>
    <button type="button"
            wire:click="addProduct"
            class="btn btn-lg btn-secondary w-100 animate-slide-end order-sm-2 order-md-4 font-neue"
            style="background: #ffffff"
            onclick="pixelAddToCart(123, 1500, 'iPhone 15')"
            aria-label="კალათაში დამატება"
            wire:loading.attr="disabled">
        <i class="ci-shopping-cart fs-base animate-target" style="color: #252525"></i>
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