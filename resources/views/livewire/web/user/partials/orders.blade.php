@if($orders->count() > 0)
    <div class="table-responsive">
        <table class="table align-middle fs-sm text-nowrap">
            <thead>
            <tr>
                <th scope="col" class="py-3 ps-0">
                    <span class="text-body fw-normal">შეკვეთა <span class="d-none d-md-inline">#</span></span>
                </th>
                <th scope="col" class="py-3 d-none d-md-table-cell">
                    <span>თარიღი</span>
                </th>
                <th scope="col" class="py-3 d-none d-md-table-cell">
                    <span class="text-body fw-normal">სტატუსი</span>
                </th>
                <th scope="col" class="py-3 d-none d-md-table-cell">
                    <span>ღირებულება</span>
                </th>
                <th scope="col" class="py-3">&nbsp;</th>
            </tr>
            </thead>
            <tbody class="text-body-emphasis">
            @foreach($orders as $order)
                <tr wire:key="order-{{ $order->id }}">
                    <!-- Order Number -->
                    <td class="fw-medium pt-2 pb-3 py-md-2 ps-0">
                        <a class="d-inline-block animate-underline text-body-emphasis text-decoration-none py-2"
                           href=""
                           wire:navigate>
                            <span class="animate-target">{{ $order->id }}</span>
                        </a>

                        <!-- Mobile Info -->
                        <ul class="list-unstyled fw-normal text-body m-0 d-md-none">
                            <li>{{ $order->created_at->format('M d, Y') }}</li>
                            <li class="d-flex align-items-center">
                            </li>
                            <li class="fw-medium text-body-emphasis">{{ number_format($order->amount, 2) }} ₾</li>
                        </ul>
                    </td>

                    <!-- Date (Desktop) -->
                    <td class="fw-medium py-3 d-none d-md-table-cell">
                        {{ $order->created_at->format('M d, Y') }}
                    </td>

                    <!-- Status (Desktop) -->
                    <td class="fw-medium py-3 d-none d-md-table-cell">
                    </td>

                    <!-- Total (Desktop) -->
                    <td class="fw-medium py-3 d-none d-md-table-cell">
                        {{ number_format($order->amount, 2) }} ₾
                    </td>

                    <!-- Products Thumbnails -->
                    <td class="py-3 pe-0">
        <span class="d-flex align-items-center justify-content-end position-relative gap-1 gap-sm-2 ms-n2 ms-sm-0">
            @foreach($order->items->take(3) as $item)
                <span>
                    <img src="{{ asset('storage/' . $item->product->main_image) }}"
                         width="64"
                         alt="{{ $item->product->translation(app()->getLocale())->title ?? $item->product->translation('ka')->title }}"
                         loading="lazy">
                </span>
            @endforeach

            @if($order->items->count() > 3)
                <span class="fw-medium me-1">+{{ $order->items->count() - 3 }}</span>
            @endif

            <a class="btn btn-icon btn-ghost btn-secondary stretched-link border-0"
               href=""
               wire:navigate
               aria-label="შეკვეთის დეტალები">
                <i class="ci-chevron-right fs-lg"></i>
            </a>
        </span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <nav class="pt-3 pb-2 pb-sm-0 mt-2 mt-md-3" aria-label="შეკვეთების პაგინაცია">
        {{ $orders->links() }}
    </nav>
@else
    @include('livewire.web.user.orders.partials.empty-state')
@endif