<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-fluid p-0">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <section class="app-user-view-account">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
                        <div class="card">
                            <div class="card-body">
                                <div class="info-container">
                                    <ul class="list-unstyled">
                                        <li class="mb-75 d-flex justify-content-between">
                                            <span class="fw-bolder me-25 font-neue">სახელი გვარი:</span>
                                            <span>{{ $user->name }} {{ $user->lastname }}</span>
                                        </li>
                                        <li class="mb-75 d-flex justify-content-between">
                                            <span class="fw-bolder me-25 font-neue">ელ-ფოსტა:</span>
                                            <span>
                                                {{ $user->email }}
                                                @if($user->verify_email === 1)
                                                    <i class="text-success" data-feather="check-circle"></i>
                                                @else
                                                    <i class="text-danger" data-feather="x-circle"></i>
                                                @endif
                                            </span>
                                        </li>
                                        <li class="mb-75 d-flex justify-content-between">
                                            <span class="fw-bolder me-25 font-neue">ტელეფონის ნომერი:</span>
                                            <span>
                                                {{ $user->phone }}
                                                @if($user->verify_phone === 1)
                                                    <i class="text-success" data-feather="check-circle"></i>
                                                @else
                                                    <i class="text-danger" data-feather="x-circle"></i>
                                                @endif
                                            </span>
                                        </li>
                                        <li class="mb-75 d-flex justify-content-between">
                                            <span class="fw-bolder me-25 font-neue">წვდომის ჯგუფი:</span>
                                            <span class="badge badge-light-success">{{ $user->role->name }}</span>
                                        </li>
                                        <li class="mb-75 d-flex justify-content-between">
                                            <span class="fw-bolder me-25 font-neue">დაბადების თარიღი:</span>
                                            <span>{{ \Carbon\Carbon::parse($user->birthday_date)->format('d-m-Y') }}</span>
                                        </li>
                                        @if(!$user->trashed() && $user->role_id != 2)
                                        <li class="mb-75 d-flex justify-content-between">
                                            <span class="fw-bolder me-25 font-neue">სტატუსი:</span>
                                            <div class="d-flex justify-content-center">
                                                <div class="form-check form-switch form-check-success">
                                                    <input type="checkbox" class="form-check-input" id="user_active_{{ $user->id }}" wire:click="toggleActive({{ $user->id }})" @checked($user->active) />
                                                    <label class="form-check-label" for="user_active_{{ $user->id }}">
                                                        <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                        <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </li>
                                        @endif
                                    </ul>
                                    @if(!$user->trashed() && $user->role_id != 2)
                                    <div class="d-flex justify-content-center pt-2">
                                        <a href="javascript:;" class="btn btn-primary me-1" data-bs-target="#editUser" data-bs-toggle="modal">
                                            რედაქტირება
                                        </a>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
                        <ul class="nav nav-pills mb-2">
                            <li class="nav-item">
                                <a class="nav-link @if(!request()->segment(5)) active @endif" href="{{ route('dashboard.user.view', $user->id) }}">
                                    <i data-feather="shopping-cart" class="font-medium-3 me-50"></i>
                                    <span class="fw-bold">შეკვეთები</span></a>
                            </li>
                        </ul>
                        @if(count($user->orders) > 0)
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">შეკვეთების ჩამონათვალი</h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr class="text-center">
                                            <th>ID</th>
                                            <th>თარიღი</th>
                                            <th>შეკვეთის სტატუსი</th>
                                            <th>მოქმედება</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($user->orders as $order)
                                    <tr class="text-center">
                                        <th>{{ $order->id }}</th>
                                        <th>{{ $order->created_at->format('d-m-Y i:s') }}</th>
                                        <th>
                                            <span class="badge badge-light-{{$order->status->badge_class}}">
                                               {{ $order->status->translations->where('locale', 'ka')->first()->title }}
                                            </span>
                                        </th>
                                        <td>
                                            <a href="{{ route('dashboard.order.view', $order->id) }}" class="text-body">
                                                <i data-feather="eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
