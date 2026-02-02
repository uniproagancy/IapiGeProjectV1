<div>
    <div wire:ignore.self class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 font-neue" id="checkoutModalLabel" style="font-size: 16px">
                        @if($step === 'checkout')
                            სწრაფი შეძენა
                        @elseif($step === 'otp')
                            ტელეფონის ნომრის დადასტურება
                        @endif
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            @if($step === 'otp') disabled @endif></button>
                </div>
                <div class="modal-body">
                    @if($step === 'checkout')
                        <form wire:submit.prevent="submitCheckout">
                            @if($selectedProduct)
                                <div class="alert alert-info mb-4" role="alert">
                                    <div class="d-flex gap-3">
                                        <img src="{{ asset('storage/' . $selectedProduct->main_image) }}"
                                             alt="{{ $selectedProduct->translations->where('locale', app()->getLocale())->first()->title ?? $selectedProduct->translations->where('locale', 'ka')->first()->title }}"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <div>
                                            <h6 class="mb-1 font-neue">{{ $selectedProduct->translations->where('locale', app()->getLocale())->first()->title ?? $selectedProduct->translations->where('locale', 'ka')->first()->title }}</h6>
                                            @if(!empty($selectedProduct->price->discount_price))
                                                <div class="h5 lh-1 mb-0" style="font-size: 14px">
                                                    {{ number_format($selectedProduct->price->discount_price, 2) }} ₾
                                                    <del class="text-body-tertiary fs-sm fw-normal">
                                                        {{ number_format($selectedProduct->price->regular_price, 2) }}
                                                    </del>
                                                </div>
                                            @else
                                                <div class="h5 lh-1 mb-0" style="font-size: 14px">
                                                    {{ number_format($selectedProduct->price->regular_price, 2) }} ₾
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="section-title mb-3">
                                <h5 class="font-neue mb-0" style="font-size: 15px">პირადი ინფორმაცია</h5>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label font-neue"
                                           style="font-size: 12px">სახელი</label>
                                    <input type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           id="name"
                                           wire:model.blur="name"
                                           required>
                                    @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastname" class="form-label font-neue"
                                           style="font-size: 12px">გვარი</label>
                                    <input type="text"
                                           class="form-control @error('lastname') is-invalid @enderror"
                                           id="lastname"
                                           wire:model.blur="lastname"
                                           required>
                                    @error('lastname')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label font-neue"
                                           style="font-size: 12px">ელ-ფოსტა</label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           id="email"
                                           wire:model.blur="email"
                                           placeholder="მაგ: example@gmail.com"
                                           required>
                                    @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label font-neue" style="font-size: 12px">ტელეფონის
                                        ნომერი</label>
                                    <input type="tel"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           id="phone"
                                           wire:model.blur="phone"
                                           placeholder="მაგ: +995 555 123456"
                                           required>
                                    @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="section-title mb-3 mt-4">
                                <h5 class="font-neue mb-0" style="font-size: 15px">მიწოდების მისამართი</h5>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label font-neue"
                                       style="font-size: 12px">მისამართი</label>
                                <input type="text"
                                       class="form-control @error('address') is-invalid @enderror"
                                       id="address"
                                       wire:model.blur="address"
                                       placeholder="მაგ: თბილისი, ვაკე, მეცხოვრის ქ. 1"
                                       required>
                                @error('address')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label font-neue" style="font-size: 12px">კომენტარი
                                    (არასავალდებულო)</label>
                                <textarea class="form-control"
                                          id="comment"
                                          wire:model.blur="comment"
                                          rows="3"
                                          placeholder="დამატებითი ინფორმაცია..."></textarea>
                            </div>
                            <div class="section-title mb-3 mt-4">
                                <h5 class="font-neue mb-0" style="font-size: 15px">გადახდის მეთოდი</h5>
                            </div>
                            <div class="row g-3 mb-3">
                                @foreach($payment_list as $payment)
                                    <div class="col-sm-{{ $payment->col ?? 3 }}">
                                        <input class="btn-check"
                                               type="radio"
                                               name="payment_id"
                                               id="payment_{{ $payment->id }}"
                                               value="{{ $payment->id }}"
                                               wire:model.live="payment_id">
                                        <label class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4"
                                               for="payment_{{ $payment->id }}"
                                               style="height: 90px !important; font-size: 12px">
                                            <img src="{{ asset('storage/uploads/payments/'.$payment->icon) }}"
                                                 @if($payment->id === 1 OR $payment->id === 2) width="45" @else width="160" @endif>
                                            <span class="fw-semibold mb-1 font-neue"
                                                  style="word-break: break-word; overflow-wrap: break-word; white-space: normal; line-height: 1.3; max-width: 100%">
                                            {{ $payment->translations->where('locale', app()->getLocale())->first()->title ?? '' }}
                                        </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit"
                                        class="btn btn-lg btn-primary font-neue"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove>გადახდა</span>
                                    <span wire:loading>
                                <span class="spinner-border spinner-border-sm me-2" role="status"
                                      aria-hidden="true"></span>
                                შედეგს ელოდება...
                            </span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .section-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .payment-option {
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            background-color: #f9f9f9 !important;
            border-color: #007bff !important;
        }

        .payment-option input[type="radio"]:checked ~ label {
            color: #007bff;
        }

        .payment-option input[type="radio"]:checked {
            border-color: #007bff;
        }

        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        #otp_code {
            letter-spacing: 10px;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
    </style>
</div>