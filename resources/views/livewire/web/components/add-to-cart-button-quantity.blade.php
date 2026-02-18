<div class="d-flex flex-wrap flex-sm-nowrap flex-md-wrap flex-lg-nowrap gap-3 gap-lg-2 gap-xl-3 mb-4">
    <div class="count-input flex-shrink-0 order-sm-1">
        <button type="button"
                class="btn btn-icon btn-lg"
                wire:click="decrementQuantity"
                aria-label="რაოდენობის შემცირება">
            <i class="ci-minus"></i>
        </button>
        <input type="number"
               class="form-control form-control-lg"
               wire:model="quantity"
               min="1"
               readonly>
        <button type="button"
                class="btn btn-icon btn-lg"
                wire:click="incrementQuantity"
                aria-label="რაოდენობის გაზრდა">
            <i class="ci-plus"></i>
        </button>
    </div>
    <button type="button"
            class="btn btn-lg btn-outline-dark w-100 animate-slide-end order-sm-2 order-md-4 font-neue"
            style="font-size: 14px"
            data-url="{{ url()->current() }}"
            wire:click="addProduct"
            wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="addProduct">
            <i class="ci-shopping-cart fs-lg animate-target ms-n1 me-2"></i>
            კალათაში დამატება
        </span>
        <span wire:loading wire:target="addProduct">
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
        </span>
    </button>
</div>
<script>
    document.addEventListener('livewire:init', () => {
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
    });
</script>