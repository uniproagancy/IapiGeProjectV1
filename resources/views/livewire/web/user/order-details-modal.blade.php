<div wire:ignore.self class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-neue">
                    @if($selectedOrder)
                        შეკვეთა # {{ $selectedOrder->id }}
                    @endif
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            @if($selectedOrder)
                <div class="modal-body">
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="mb-2 text-center">
                                    <b class="text-muted d-block font-neue" style="font-size: 13px;">თარიღი</b>
                                    <span style="font-size: 14px">{{ $selectedOrder->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-2 text-center">
                                    <b class="text-muted d-block font-neue" style="font-size: 13px;">შეკვეთის სტატუსი</b>
                                    <span class="badge {{ $selectedOrder->status->badge_class }}">
                                        {{ $selectedOrder->status->translations->where('locale', 'ka')->first()->title  }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-2 text-center">
                                    <b class="text-muted d-block font-neue" style="font-size: 13px;">გადახდის მეთოდი</b>
                                    <span style="font-size: 13px">{{ $selectedOrder->payment->translations->where('locale', 'ka')->first()->title ?? 'გაუნსაზღვრელი' }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-2 text-center">
                                    <b class="text-muted d-block font-neue" style="font-size: 13px;">გადახდის სტატუსი</b>
                                    <span class="badge {{ $selectedOrder->status->badge_class }}">
                                        {{ $selectedOrder->paymentStatus->translations->where('locale', 'ka')->first()->title  }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-4 mb-4">
                        <h6 class="font-neue mb-3">მიწოდების დეტალები</h6>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="mb-3 col-md-4">
                                        <b class="text-muted d-block font-neue" style="font-size: 13px;">მომხმარებელი</b>
                                        <span style="font-size: 14px">{{ $selectedOrder->user->name }} {{ $selectedOrder->user->lastname }}</span>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <b class="text-muted d-block font-neue" style="font-size: 13px;">ტელეფონი</b>
                                        <span style="font-size: 14px">{{ $selectedOrder->user->phone }}</span>
                                    </div>
                                    <div class="mb-3 col-md-4">
                                        <b class="text-muted d-block font-neue" style="font-size: 13px;">ელ-ფოსტა</b>
                                        <span style="font-size: 14px">{{ $selectedOrder->user->email }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3 col-4">
                                    <b class="text-muted d-block font-neue" style="font-size: 13px;">მისამართი</b>
                                    <span style="font-size: 14px">{{ $selectedOrder->deliveryData->city->translations->where('locale', 'ka')->first()->name }} / {{ $selectedOrder->deliveryData->address }}</span>
                                </div>
                            </div>
                            @if($selectedOrder->comment)
                            <div class="col-md-12">
                                <div class="border-top pt-4 mt-4">
                                    <b class="text-muted d-block font-neue mb-2">კომენტარი</b>
                                    <p class="mb-0">{{ $selectedOrder->comment }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="border-top pt-4 mb-4">
                        <h6 class="font-neue mb-3">პროდუქტები</h6>
                        <div class="list-group list-group-flush">
                            @foreach($selectedOrder->items as $item)
                            <div class="list-group-item px-0 py-1">
                                <div class="row align-items-center g-3">
                                    <div class="col-auto">
                                        <img src="{{ asset('storage/' . $item->product->main_image) }}"
                                             alt="{{ $item->product->translation(app()->getLocale())->title ?? $item->product->translation('ka')->title }}"
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                    </div>
                                    <div class="col">
                                        <div>
                                            <h6 class="mb-1 font-neue" style="font-size: 14px">
                                                {{ $item->product->translation(app()->getLocale())->title ?? $item->product->translation('ka')->title }}
                                            </h6>
                                            <b class="text-muted d-block" style="font-size: 12px">SKU: {{ $item->product->sku }}</b>
                                        </div>
                                    </div>
                                    <div class="col-auto text-end">
                                        <div class="mb-2">
                                            <strong class="d-block">
                                                ({{ $item->quantity }}) X {{ number_format($item->price, 2) }} ₾
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    @if($selectedOrder->payment_id === 2)
                    <button type="button"
                            class="btn btn-warning font-neue">
                        ინვოისის გადმოწერა
                    </button>
                    @endif
                    <button type="button"
                            class="btn btn-secondary font-neue"
                            data-bs-dismiss="modal">
                        დახურვა
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
<style>
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .list-group-item {
        border-color: #f0f0f0;
    }

    .list-group-item:last-child {
        border-bottom: 0;
    }
</style>