<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="row">
                @if($order->delivery)
                    <div class="col-12">
                        <div class="alert alert-success" role="alert">
                            <div class="alert-body d-flex align-items-center justify-content-between">
                                <span>აღნიშნული შეკვეთა გადაგზავნილია მისაწოდებლად! <b>კომპანია: {{ $order->delivery->delivery_company->name }}</b></span>
                                <button class="btn btn-danger" wire:click="cancelDeliveryModal">მიწოდების გაუქმება
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-12 mb-1">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0 font-neue" style="font-size: 16px">შეკვეთა #{{ $order->id }}</h4>
                            </div>
                            <div>
                                <span>{{ $order->created_at }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="btn-group">
                                <button class="btn btn-primary dropdown-toggle waves-effect waves-float waves-light"
                                        type="button" id="dropdownMenuButton6" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    მოქმედება
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton6">
                                    <a class="dropdown-item" href="#" wire:click.prevent="downloadInvoice">
                                        <i data-feather="download"></i>
                                        ინვოისის გადმოწერა
                                    </a>
                                    <a class="dropdown-item" href="#" wire:click.prevent="sendInvoice">
                                        <i data-feather="send"></i>
                                        ინვოისის გაგზავნა
                                    </a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                       data-bs-target="#updateOrderStatusModal">
                                        <i data-feather="edit"></i>
                                        შეკვეთის სტატუსი
                                    </a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                       data-bs-target="#editOrder">
                                        <i data-feather="edit-2"></i>
                                        შეკვეთის კორექტირება
                                    </a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                       data-bs-target="#sendToDeliveryCompanyModal">
                                        <i data-feather="truck"></i>
                                        საკურიეროში გადაგზავნა
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">შეკვეთის დეტალები</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">შეკვეთის სტატუსი:</span>
                                    <span class="badge badge-light-{{$order->orderStatus->badge_class}}">
                                       {{ $order->orderStatus->translations->where('locale', 'ka')->first()->title }}
                                    </span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">გადახდის მეთოდი:</span>
                                    <span class="badge badge-light-success">
                                        {{ $order->payment->translations->where('locale', 'ka')->first()->title }}
                                    </span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">გადახდის სტატუსი:</span>
                                    <span class="text-{{ $order->paymentStatus->badge_class }}">
                                        <i data-feather="circle"></i>
                                        {{ $order->paymentStatus->translations->where('locale', 'ka')->first()->title }}
                                   </span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">შეკვეთის სრული ღირებულება:</span>
                                    <span class="badge badge-light-success">{{ number_format($order->amount + $order->delivery_amount, 2)  }} ₾</span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">პროდუქციის ღირებულება:</span>
                                    <span class="badge badge-light-success">{{ number_format($order->amount, 2)  }} ₾</span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">მიწოდების ღირებულება:</span>
                                    <span class="badge badge-light-success">{{ number_format($order->delivery_amount, 2)  }} ₾</span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">მიწოდების მისამართი:</span>
                                    <span class="badge badge-light-warning">{{ $order->deliveryData->address }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">მომხმარებელი</h4>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">მომხმარებელი:</span>
                                    <span>{{ $order->user->name }} {{ $order->user->lastname }}</span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">ელ-ფოსტა:</span>
                                    <span>{{ $order->user->email }}</span>
                                </li>
                                <li class="mb-75 d-flex justify-content-between">
                                    <span class="fw-bolder me-25 font-neue">ტელეფონის ნომერი:</span>
                                    <span>{{ $order->user->phone }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">პროდუქცია</h4>
                        </div>
                        @if(!empty($order->items))
                            <table class="table table-striped">
                                <thead>
                                <tr class="text-center">
                                    <th>სურათი</th>
                                    <th class="text-start">პროდუქტი</th>
                                    <th>ღირებულება</th>
                                    <th>რაოდენობა</th>
                                    <th>ჯამი</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($order->items as $item)
                                    <tr class="text-center">
                                        <td>
                                            <div class="avatar-group">
                                                <div data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                     data-bs-placement="top" class="avatar pull-up my-0">
                                                    <img src="{{ asset('storage/' . $item->product->main_image) }}"
                                                         alt="" height="40" width="40"/>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge badge-light-info">{{ $item->product->sku }}</span> -
                                            <a href="{{ route('web.products.view', $item->product->translations->where('locale', 'ka')->first()->slug) }}" target="_blank" > {{ $item->product->translations->where('locale', 'ka')->first()->title }} </a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">{{ number_format($item->price, 2)  }} ₾</span>
                                        </td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            <span class="badge badge-light-success">{{ number_format($item->price * $item->quantity) }} ₾</span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    @if(!empty($order->comment))
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">შეკვეთის კომენტარი</h4>
                            </div>
                            <div class="card-body">
                                {{ $order->comment }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="updateOrderStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <form wire:submit.prevent="updateOrderStatus" class="modal-content pt-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">შეკვეთის სტატუსის რედაქტირება</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">შეკვეთის სტატუსი</label>
                        <select wire:model="status_id" class="form-select">
                            @foreach($order_statuses as $order_status)
                                <option value="{{ $order_status->id }}">{{ $order_status->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">გადახდის სტატუსი</label>
                        <select wire:model="payment_status_id" class="form-select">
                            @foreach($payment_statuses as $payment_status)
                                <option value="{{ $payment_status->id }}">{{ $payment_status->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary">განახლება</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div wire:ignore.self class="modal fade" id="sendToDeliveryCompanyModal" tabindex="-1"
         aria-labelledby="sendToDeliveryCompanyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendToDeliveryCompanyModalLabel">
                        საკურიეროში გადაგზავნა
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">აირჩიეთ საკურიერო</label>
                        <select class="form-select @error('delivery_company_id') border-danger is-invalid @enderror"
                                wire:model="delivery_company_id">
                            <option value="">-- აირჩიეთ --</option>
                            @foreach($delivery_companies as $delivery_company)
                                <option value="{{ $delivery_company->id }}">
                                    {{ $delivery_company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('delivery_company_id')
                        <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="sendToDeliveryCompany" class="btn btn-primary">
                        გადაგზავნა
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        დახურვა
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@section('page_scripts')
    <script>
        Livewire.on('status_modal_close', () => {
            const modalEl = document.getElementById('updateOrderStatus');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('delivery_company_modal_close', () => {
            const modalEl = document.getElementById('sendToDeliveryCompanyModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('swal:cancelDeliveryModal', data => {
            Swal.fire({
                title: data[0].title,
                icon: data[0].icon,
                showCancelButton: true,
                confirmButtonText: data[0].confirmButtonText,
                cancelButtonText: data[0].cancelButtonText,
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('cancelDelivery');
                }
            });
        });
    </script>
@endsection