@if($orders->count() > 0)
    <div class="table-responsive">
        <table class="table align-middle fs-sm text-nowrap">
            <thead>
            <tr class="font-neue">
                <th scope="col" class="py-3 ps-0">
                    <span class="text-body">შეკვეთა <span class="d-none d-md-inline">#</span></span>
                </th>
                <th scope="col" class="py-3 d-none d-md-table-cell">
                    <span>თარიღი</span>
                </th>
                <th scope="col" class="py-3 d-none d-md-table-cell text-center">
                    <span class="text-body">სტატუსი</span>
                </th>
                <th scope="col" class="py-3 d-none d-md-table-cell text-center">
                    <span>ღირებულება</span>
                </th>
                <th scope="col" class="py-3">&nbsp;</th>
            </tr>
            </thead>
            <tbody class="text-body-emphasis">
            @foreach($orders as $order)
                <tr wire:key="order-{{ $order->id }}">
                    <td class="fw-medium pt-2 pb-3 py-md-2 ps-0">
                        <span class="d-inline-block py-2">
                            {{ $order->id }}
                        </span>
                        <ul class="list-unstyled fw-normal text-body m-0 d-md-none">
                            <li>{{ $order->created_at->format('M d, Y') }}</li>
                            <li class="fw-medium text-body-emphasis">{{ number_format($order->amount, 2) }} ₾</li>
                        </ul>
                    </td>
                    <td class="fw-medium py-3 d-none d-md-table-cell">
                        {{ $order->created_at->format('M d, Y') }}
                    </td>
                    <td class="fw-medium py-3 d-none d-md-table-cell text-center">
                        <span class="badge {{ $order->status->badge_class }}">
                            {{ $order->status->translation('ka')->title }}
                        </span>
                    </td>
                    <td class="fw-medium py-3 d-none d-md-table-cell text-center">
                        {{ number_format($order->amount, 2) }} ₾
                    </td>
                    <td class="py-3 pe-0">
                        <span class="d-flex align-items-center justify-content-end position-relative gap-1 gap-sm-2 ms-n2 ms-sm-0">
                            @foreach($order->items->take(3) as $item)
                            <span>
                                <img src="{{ asset('storage/' . $item->product->main_image) }}"
                                     width="64"
                                     alt="{{ $item->product->translation(app()->getLocale())->title ?? $item->product->translation('ka')->title }}"
                                     loading="lazy"
                                     style="border-radius: 4px; object-fit: cover; width: 64px; height: 64px;">
                            </span>
                            @endforeach
                            @if($order->items->count() > 3)
                                <span class="fw-medium me-1" style="font-size: 12px;">
                                    +{{ $order->items->count() - 3 }}
                                </span>
                            @endif
                            <button type="button"
                                    class="btn btn-icon btn-ghost btn-secondary border-0"
                                    wire:click="viewOrderDetails({{ $order->id }})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#orderDetailsModal"
                                    title="შეკვეთის დეტალები"
                                    aria-label="შეკვეთის დეტალები">
                                <i class="ci-chevron-right fs-lg"></i>
                            </button>
                        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @include('livewire.web.user.order-details-modal')
@else
    <div class="text-center py-5">
        <svg class="text-muted mb-4" width="120" height="120" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
        </svg>
        <h3 class="h5 mb-2 font-neue">შეკვეთები არ არის</h3>
        <p class="text-muted mb-4">თქვენ ჯერ არ გაქვთ შეკვეთები</p>
        <a href="{{ route('web.products.index') }}" class="btn btn-primary font-neue">
            დაიწყეთ შოპინგი
        </a>
    </div>
@endif