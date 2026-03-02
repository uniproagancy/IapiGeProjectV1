@php
    $price = !empty($product->price->discount_price)
        ? $product->price->discount_price
        : $product->price->regular_price;
    $formattedPrice = number_format($price, 0);
@endphp

<div class="border rounded p-3 mt-3">

    @if($success)
        <div class="text-center py-3">
            <div class="text-success mb-2" style="font-size: 40px;">✓</div>
            <div class="fw-semibold font-neue mb-1">შეკვეთა მიღებულია!</div>
            <div class="text-muted" style="font-size: 13px;">ჩვენი ოპერატორი მალე დაგიკავშირდებათ</div>
        </div>
    @else

        <div class="text-center mb-3">
            <span class="fw-semibold" style="color: #e91e63; font-size: 14px;">
                ✓ მიწოდება მთელი ქვეყნის მასშტაბით
            </span>
        </div>

        <hr class="my-2">

        <div class="d-flex flex-column gap-2 mb-3">

            <div class="d-flex align-items-center justify-content-between p-2 rounded"
                 style="border: 1px solid {{ $delivery === 'courier' ? '#0d6efd' : '#dee2e6' }};
                        background: {{ $delivery === 'courier' ? '#f0f5ff' : '' }};
                        cursor: pointer;"
                 wire:click="$set('delivery', 'courier')">
                <div class="d-flex align-items-center gap-2">
                    <input type="radio" wire:model="delivery" value="courier" class="form-check-input mt-0">
                    <label class="mb-0" style="cursor: pointer; font-size: 14px;">გადახდა კურიერთან</label>
                </div>
                <span class="text-muted" style="font-size: 13px;">ფასი: {{ $formattedPrice }} ₾</span>
            </div>

            <div class="d-flex align-items-center justify-content-between p-2 rounded"
                 style="border: 1px solid {{ $delivery === 'installment' ? '#0d6efd' : '#dee2e6' }};
                        background: {{ $delivery === 'installment' ? '#f0f5ff' : '' }};
                        cursor: pointer;"
                 wire:click="$set('delivery', 'installment')">
                <div class="d-flex align-items-center gap-2">
                    <input type="radio" wire:model="delivery" value="installment" class="form-check-input mt-0">
                    <label class="mb-0" style="cursor: pointer; font-size: 14px;">განვადება</label>
                </div>
                <span class="text-muted" style="font-size: 13px;">ფასი: {{ $formattedPrice }} ₾</span>
            </div>

            <div class="d-flex align-items-center justify-content-between p-2 rounded"
                 style="border: 1px solid {{ $delivery === 'bank' ? '#0d6efd' : '#dee2e6' }};
                        background: {{ $delivery === 'bank' ? '#f0f5ff' : '' }};
                        cursor: pointer;"
                 wire:click="$set('delivery', 'bank')">
                <div class="d-flex align-items-center gap-2">
                    <input type="radio" wire:model="delivery" value="bank" class="form-check-input mt-0">
                    <label class="mb-0" style="cursor: pointer; font-size: 14px;">საბანკო გადარიცხვა</label>
                </div>
                <span class="text-muted" style="font-size: 13px;">ფასი: {{ $formattedPrice }} ₾</span>
            </div>

            <div class="d-flex align-items-center justify-content-between p-2 rounded"
                 style="border: 1px solid {{ $delivery === 'card' ? '#0d6efd' : '#dee2e6' }};
                        background: {{ $delivery === 'card' ? '#f0f5ff' : '' }};
                        cursor: pointer;"
                 wire:click="$set('delivery', 'card')">
                <div class="d-flex align-items-center gap-2">
                    <input type="radio" wire:model="delivery" value="card" class="form-check-input mt-0">
                    <label class="mb-0" style="cursor: pointer; font-size: 14px;">ბარათით ყიდვა</label>
                </div>
                <span class="text-muted" style="font-size: 13px;">ფასი: {{ $formattedPrice }} ₾</span>
            </div>

        </div>

        <div class="mb-2">
            <input type="tel"
                   wire:model="phone"
                   class="form-control @error('phone') is-invalid @enderror"
                   placeholder="ტელეფონის ნომერი"
                   style="font-size: 14px;">
            @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <input type="text"
                   wire:model="name"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="სახელი და გვარი"
                   style="font-size: 14px;">
            @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button wire:click="placeOrder"
                wire:loading.attr="disabled"
                class="btn btn-lg w-100 font-neue"
                style="background-color: #4caf50; color: #fff; font-size: 15px;">
            <span wire:loading.remove>შეკვეთა</span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm"></span>
            </span>
        </button>

    @endif
</div>