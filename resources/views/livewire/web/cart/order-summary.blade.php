<aside class="col-lg-4">
    <div class="position-sticky top-0" style="padding-top: 60px;">
        <div class="bg-body-tertiary rounded-4 p-4 mb-3">
            <h3 class="h5 mb-4 font-neue">შეკვეთის შემაჯამებელი</h3>
            <div class="border-bottom pb-3 mb-3">
                @foreach($orderItems as $item)
                    <div class="d-flex align-items-center gap-2 mb-3" wire:key="checkout-item-{{ $item['id'] }}">
                        <div class="ratio ratio-1x1 flex-shrink-0" style="width: 55px;">
                            <img src="{{ asset('storage/' . $item['image']) }}"
                                 alt="{{ $item['name'] }}"
                                 class="rounded"
                                 loading="lazy">
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-medium text-truncate small" style="font-size: 12px">{{ $item['name'] }}</div>
                            <div class="text-muted small">{{ $item['quantity'] }}
                                × {{ number_format($item['price'], 2) }} ₾
                            </div>
                        </div>
                        <div class="fw-semibold" style="font-size: 13px">
                            {{ number_format($item['total'], 2) }}₾
                        </div>
                    </div>
                @endforeach
            </div>
            <ul class="list-unstyled fs-sm gap-2 mb-0">
                <li class="d-flex justify-content-between">
                    <span style="font-size: 12px">ღირებულება: </span>
                    <span class="fw-medium">{{ number_format($subtotal, 2) }} ₾</span>
                </li>
            </ul>
            <div class="border-top pt-3 mt-3">
                <div class="d-flex justify-content-between mb-3">
                    <span class="h6 mb-0" style="font-size: 14px">გადასახდელი თანხა:</span>
                    <span class="h5 mb-0 text-primary" style="font-size: 14px">{{ number_format($total, 2) }} ₾</span>
                </div>

                <!-- Submit Button (Desktop) -->
                <button type="submit"
                        class="btn btn-lg btn-primary w-100 d-none d-lg-block font-neue"
                        wire:click="placeOrder"
                        wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="placeOrder">
                    შეკვეთის დადასტურება
                </span>
                    <span wire:loading wire:target="placeOrder">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    მუშავდება...
                </span>
                </button>
            </div>
        </div>
    </div>
</aside>