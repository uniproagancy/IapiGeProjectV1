<div>
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">შეკვეთების ჩამონათვალი</h4>
                        <div>
                            <button type="button" class="btn btn-icon btn-success mx-50" data-bs-toggle="modal"
                                    data-bs-target="#createOrderModal">
                                <i data-feather="plus-square"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#filterOrderModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                    @if(count($orders) > 0)
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
                                        <th>{{ $order->id }}</th>
                                        <th>{{ $order->user->name }} {{ $order->user->lastname }}</th>
                                        <th>{{ $order->created_at }}</th>
                                        <th>{{ number_format($order->amount + $order->delivery_amount, 2) }} ₾</th>
                                        <td>
                                        <span class="badge badge-light-primary">
                                           {{ $order->payment->translations->where('locale', 'ka')->first()->title ?? '' }}
                                        </span>
                                        </td>
                                        <td>
                                       <span class="text-{{ $order->paymentStatus->badge_class }}">
                                            <i data-feather="circle"></i>
                                            {{ $order->paymentStatus->translations->where('locale', 'ka')->first()->title }}
                                       </span>
                                        </td>
                                        <th>
                                        <span class="badge badge-light-{{ $order->orderStatus->badge_class}}">
                                           {{ $order->orderStatus->translations->where('locale', 'ka')->first()->title }}
                                        </span>
                                        </th>
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
                </div>
                @else
                    <div class="px-2">
                        <div class="alert alert-warning" role="alert">
                            <div class="alert-body d-flex align-items-center">
                                <i data-feather="alert-circle" class="me-50"></i>
                                <span> ჩამონათვალი ცარიელია!</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
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
    </script>
@endsection