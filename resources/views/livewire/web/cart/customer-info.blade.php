<div class="bg-body-tertiary rounded-4 p-4 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h5 mb-0 font-neue">პირადი ინფორმაცია</h2>
    </div>
    @auth
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="d-flex align-items-center p-3 bg-white rounded-3 h-100">
                    <div class="flex-shrink-0">
                        <div class="d-flex justify-content-center align-items-center"
                             style="width: 40px; height: 40px; border-radius: 20px">
                            <i class="ci-user text-primary fs-5"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="text-muted small mb-1 font-neue" style="font-size: 13px">მომხმარებელი</div>
                        <div class="fw-semibold" style="font-size: 13px">{{ $name }} {{ $lastname }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="d-flex align-items-center p-3 bg-white rounded-3 h-100">
                    <div class="flex-shrink-0">
                        <div class="d-flex justify-content-center align-items-center"
                             style="width: 40px; height: 40px; border-radius: 20px">
                            <i class="ci-mail text-success fs-5"></i>
                        </div>
                    </div>
                    <div class="ms-3 min-w-0 flex-grow-1">
                        <div class="text-muted small mb-1 font-neue" style="font-size: 13px">ელ-ფოსტა</div>
                        <div class="fw-semibold text-truncate" style="font-size: 13px">{{ $email }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="d-flex align-items-center p-3 bg-white rounded-3 h-100 position-relative">
                    <div class="flex-shrink-0">
                        <div class="d-flex justify-content-center align-items-center"
                             style="width: 40px; height: 40px; border-radius: 20px">
                            <i class="ci-phone text-info fs-5"></i>
                        </div>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <div class="text-muted small mb-1 font-neue" style="font-size: 13px">ტელეფონი</div>
                        <div class="fw-semibold" style="font-size: 13px">{{ $phone ?: 'მიუთითეთ ტელეფონი' }}</div>
                    </div>
                    @if($verify_phone)
                        <span class="badge bg-success">ვერიფიცირებული</span>
                    @else
                        <span class="badge bg-danger">არავერიფიცირებული</span>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="row g-3">
            <div class="col-sm-6">
                <label for="first_name" class="form-label">სახელი <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ci-user"></i>
                    </span>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="first_name"
                           wire:model="name"
                           placeholder="თქვენი სახელი">
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <label for="last_name" class="form-label">გვარი <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ci-user"></i>
                    </span>
                    <input type="text"
                           class="form-control @error('lastname') is-invalid @enderror"
                           id="last_name"
                           wire:model="lastname"
                           placeholder="თქვენი გვარი">
                    @error('lastname')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <label for="email" class="form-label">ელფოსტა <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ci-mail"></i>
                    </span>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           wire:model="email"
                           placeholder="example@mail.com">
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-sm-6">
                <label for="phone" class="form-label">ტელეფონი <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ci-phone"></i>
                    </span>
                    <input type="tel"
                           class="form-control @error('phone') is-invalid @enderror"
                           id="phone"
                           wire:model.debounce-500ms="phone"
                           placeholder="+995 5__ __ __ __">
                    @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
        <div class="alert alert-info d-flex align-items-start mt-3 mb-0">
            <i class="ci-info-circle fs-5 me-2 mt-1"></i>
            <div class="fs-sm">
                გაქვთ ანგარიში? გაიარეთ
                <a href="#"
                   data-bs-toggle="modal"
                   data-bs-target="#loginModal" class="alert-link fw-semibold">ავტორიზაცია</a>
            </div>
        </div>
    @endauth
</div>