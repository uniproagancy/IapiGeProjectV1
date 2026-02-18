<div>
    <button type="button"
            wire:click="addProduct"
            class="product-card-button btn btn-icon btn-primary animate-slide-end ms-2"
            aria-label="კალათაში დამატება"
            wire:loading.attr="disabled">
        <i class="ci-shopping-cart fs-base animate-target"></i>
    </button>
</div>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('fb-add-to-cart', (data) => {
            console.log(data);
            const item = Array.isArray(data) ? data[0] : data;
            fbq('track', 'AddToCart', {
                content_ids: [item.id],
                content_type: 'product',
                content_name: item.name,
                value: item.price,
                currency: 'GEL',
                contents: [{ id: item.id, quantity: item.quantity }]
            }, {
                eventID: item.eventId
            });
        });
    }, { once: true }); // ✅ ერთხელ დარეგისტრირდება
</script>