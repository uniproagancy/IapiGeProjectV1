{{-- მიწოდების ინფო + შეკვეთის ფორმა --}}
<div class="border rounded p-3 mt-3">

    {{-- სათაური --}}
    <div class="text-center mb-3">
        <span class="fw-semibold" style="color: #e91e63; font-size: 14px;">
            ✓ მიწოდება მთელი ქვეყნის მასშტაბით
        </span>
    </div>

    <hr class="my-2">

    {{-- გადახდის ვარიანტები --}}
    <div class="d-flex flex-column gap-2 mb-3">

        {{-- გადახდა კურიერთან --}}
        <div class="d-flex align-items-center justify-content-between p-2 rounded"
             style="border: 1px solid #dee2e6; cursor: pointer;"
             onclick="selectPaymentOption('courier', this)">
            <div class="d-flex align-items-center gap-2">
                <input type="radio" name="payment_option" id="opt_courier" value="courier"
                       class="form-check-input mt-0" checked>
                <label for="opt_courier" class="mb-0" style="cursor: pointer; font-size: 14px;">
                    გადახდა კურიერთან
                </label>
            </div>
            <span class="text-muted" style="font-size: 13px;">
                ფასი: {{ number_format(!empty($product->price->discount_price) ? $product->price->discount_price : $product->price->regular_price, 0) }} ₾
            </span>
        </div>

        {{-- განვადება --}}
        <div class="d-flex align-items-center justify-content-between p-2 rounded"
             style="border: 1px solid #dee2e6; cursor: pointer;"
             onclick="selectPaymentOption('installment', this)">
            <div class="d-flex align-items-center gap-2">
                <input type="radio" name="payment_option" id="opt_installment" value="installment"
                       class="form-check-input mt-0">
                <label for="opt_installment" class="mb-0" style="cursor: pointer; font-size: 14px;">
                    განვადება
                </label>
            </div>
            <span class="text-muted" style="font-size: 13px;">
                ფასი: {{ number_format(!empty($product->price->discount_price) ? $product->price->discount_price : $product->price->regular_price, 0) }} ₾
            </span>
        </div>

        {{-- საბანკო გადარიცხვა --}}
        @php
            $finalPrice = !empty($product->price->discount_price)
                ? $product->price->discount_price
                : $product->price->regular_price;
            $salePrice = number_format($finalPrice * 0.9, 1);
        @endphp
        <div class="d-flex align-items-center justify-content-between p-2 rounded"
             style="border: 1px solid #dee2e6; cursor: pointer;"
             onclick="selectPaymentOption('bank', this)">
            <div class="d-flex align-items-center gap-2">
                <input type="radio" name="payment_option" id="opt_bank" value="bank"
                       class="form-check-input mt-0">
                <label for="opt_bank" class="mb-0" style="cursor: pointer; font-size: 14px;">
                    საბანკო გადარიცხვა
                </label>
            </div>
            <span class="badge"
                  style="background-color: #e91e63; font-size: 12px;">
                SALE: {{ $salePrice }} ₾
            </span>
        </div>
        {{-- ბარათით ყიდვა --}}
        <div class="d-flex align-items-center justify-content-between p-2 rounded"
             style="border: 1px solid #dee2e6; cursor: pointer;"
             onclick="selectPaymentOption('card', this)">
            <div class="d-flex align-items-center gap-2">
                <input type="radio" name="payment_option" id="opt_card" value="card"
                       class="form-check-input mt-0">
                <label for="opt_card" class="mb-0" style="cursor: pointer; font-size: 14px;">
                    ბარათით ყიდვა
                </label>
            </div>
            <span class="badge"
                  style="background-color: #e91e63; font-size: 12px;">
                SALE: {{ $salePrice }} ₾
            </span>
        </div>
    </div>

    {{-- ტელეფონი --}}
    <div class="mb-2">
        <input type="tel"
               id="quick_phone"
               class="form-control"
               placeholder="ტელეფონის ნომერი"
               style="font-size: 14px;">
    </div>

    {{-- სახელი --}}
    <div class="mb-3">
        <input type="text"
               id="quick_name"
               class="form-control"
               placeholder="სახელი და გვარი"
               style="font-size: 14px;">
    </div>

    {{-- შეკვეთის ღილაკი --}}
    <a href="#"
       id="quick_order_btn"
       onclick="submitQuickOrder({{ $product->id }})"
       class="btn btn-lg w-100 font-neue"
       style="background-color: #4caf50; color: #fff; font-size: 15px;">
        შეკვეთა
    </a>
</div>
<script>
    function selectPaymentOption(value, el) {
        // ✅ radio-ს დაჭერა
        document.getElementById('opt_' + value).checked = true;

        // ✅ active სტილი
        document.querySelectorAll('[onclick^="selectPaymentOption"]').forEach(function (item) {
            item.style.borderColor = '#dee2e6';
            item.style.backgroundColor = '';
        });
        el.style.borderColor = '#0d6efd';
        el.style.backgroundColor = '#f0f5ff';
    }

    function submitQuickOrder(productId) {
        const phone   = document.getElementById('quick_phone').value.trim();
        const name    = document.getElementById('quick_name').value.trim();
        const payment = document.querySelector('input[name="payment_option"]:checked').value;

        if (!phone) {
            alert('გთხოვთ შეიყვანოთ ტელეფონის ნომერი');
            return false;
        }

        if (!name) {
            alert('გთხოვთ შეიყვანოთ სახელი და გვარი');
            return false;
        }

        // ✅ checkout-ზე გადამისამართება payment option-ით
        const urls = {
            courier:     '{{ route('web.checkout.index', ['product_id' => $product->id]) }}',
            installment: '{{ route('web.checkout.index', ['product_id' => $product->id]) }}',
            bank:        '{{ route('web.checkout.index', ['product_id' => $product->id]) }}',
            card:        '{{ route('web.checkout.index', ['product_id' => $product->id]) }}',
        };

        window.location.href = urls[payment] + '&payment=' + payment + '&phone=' + encodeURIComponent(phone) + '&name=' + encodeURIComponent(name);
    }
</script>