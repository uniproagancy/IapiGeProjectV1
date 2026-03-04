<div>
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">შეკვეთების ჩამონათვალი ({{ $orders->total() }})</h4>
                        <div>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#filterOrderModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>

                    @if($orders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr class="text-center">
                                    <th>ID</th>
                                    <th>მომხმარებელი</th>
                                    <th>თარიღი</th>
                                    <th>სრული ღირებულება</th>
                                    <th>გადახდის მეთოდი</th>
                                    <th>გადახდის სტატუსი</th>
                                    <th>შეკვეთის სტატუსი</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr class="text-center">
                                        <td>{{ $order->id }}</td>
                                        <td>{{ $order->user->name }} {{ $order->user->lastname }}</td>
                                        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                        <td>{{ number_format($order->amount + $order->delivery_amount, 2) }} ₾</td>
                                        <td>
                                            <span class="badge badge-light-primary">
                                                {{ $order->payment->translations->where('locale', 'ka')->first()?->title ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-{{ $order->paymentStatus->badge_class ?? 'secondary' }}">
                                                <i data-feather="circle"></i>
                                                {{ $order->paymentStatus->translations->where('locale', 'ka')->first()?->title ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $order->orderStatus->badge_class ?? 'secondary' }}">
                                                {{ $order->orderStatus->translations->where('locale', 'ka')->first()?->title ?? '—' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($order->trashed())
                                                <a href="#" class="text-body"
                                                   wire:click="restoreModal({{ $order->id }})">
                                                    <i class="text-success" data-feather="rotate-ccw"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('dashboard.order.view', $order->id) }}"
                                                   class="text-body">
                                                    <i data-feather="eye"></i>
                                                </a>
                                                <a href="#" class="text-body"
                                                   wire:click="deleteModal({{ $order->id }})">
                                                    <i class="text-danger" data-feather="trash"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-2">
                            <div class="alert alert-warning" role="alert">
                                <div class="alert-body d-flex align-items-center">
                                    <i data-feather="alert-circle" class="me-50"></i>
                                    <span>ჩამონათვალი ცარიელია!</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{ $orders->links() }}
    </div>

    {{-- Filter Modal --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="applyFilters">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">ფილტრი</h5>
                </div>
                <div class="modal-body flex-grow-1">

                    <div class="mb-1">
                        <label class="form-label">საძიებო სიტყვა</label>
                        <input type="text"
                               class="form-control"
                               placeholder="ID, სახელი, ტელეფონი"
                               wire:model.lazy="search_query">
                    </div>

                    <div class="mb-1">
                        <label class="form-label">სორტირება</label>
                        <select class="form-select" wire:model.lazy="order_dir">
                            <option value="desc">ახალი → ძველი</option>
                            <option value="asc">ძველი → ახალი</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">შეკვეთის სტატუსი</label>
                        <select class="form-select" wire:model.lazy="status_id">
                            <option value="">ყველა</option>
                            <option value="1">ახალი</option>
                            <option value="2">დამუშავებული</option>
                            <option value="3">გაგზავნილი</option>
                            <option value="4">გაუქმებული</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">გადახდის მეთოდი</label>
                        <select class="form-select" wire:model.lazy="payment_id">
                            <option value="">ყველა</option>
                            <option value="2">ოპერატორი</option>
                            <option value="3">BOG ბარათი</option>
                            <option value="4">BOG განვადება</option>
                            <option value="7">TBC განვადება</option>
                            <option value="10">კურიერი</option>
                            <option value="11">განვადება</option>
                            <option value="12">საბანკო გადარიცხვა</option>
                            <option value="13">ბარათით</option>
                        </select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">ჩვენება</label>
                        <select class="form-select" wire:model.lazy="per_page">
                            <option value="25">25 ჩანაწერი</option>
                            <option value="50">50 ჩანაწერი</option>
                            <option value="100">100 ჩანაწერი</option>
                        </select>
                    </div>

                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="with_trashed"
                               wire:model.lazy="with_trashed">
                        <label class="form-check-label" for="with_trashed">წაშლილი შეკვეთების ჩვენება</label>
                    </div>

                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1">გაფილტრე</button>
                        <button type="button" class="btn btn-outline-secondary"
                                wire:click="resetFilters">გასუფთავება</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@section('page_scripts')
    <script>
        Livewire.on('swal:deleteModal', data => {
            Swal.fire({
                title: data[0].title,
                icon: data[0].icon,
                showCancelButton: true,
                confirmButtonText: data[0].confirmButtonText,
                cancelButtonText: data[0].cancelButtonText,
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('delete', {id: data[0].id});
                }
            });
        });

        Livewire.on('swal:restoreModal', data => {
            Swal.fire({
                title: data[0].title,
                icon: data[0].icon,
                showCancelButton: true,
                confirmButtonText: data[0].confirmButtonText,
                cancelButtonText: data[0].cancelButtonText,
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('restore', {id: data[0].id});
                }
            });
        });

        Livewire.on('filter_modal_close', () => {
            const modalEl = document.getElementById('filterOrderModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    </script>
@endsection