<div class="col-lg-12">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 font-neue">ჩემი პროფილი</h1>
    </div>
    <div class="tab-content">
        <div class="card border-0 shadow-sm">
            <div class="card-body mt-2 px-5">
                <h5 class="mb-3 font-neue" style="font-size: 16px">ინფორმაცია მომხმარებელზე</h5>
                <form wire:submit.prevent="updatePersonalInfo">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">
                                სახელი <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   wire:model="name">
                            @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">
                                გვარი <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('lastname') is-invalid @enderror"
                                   id="lastname"
                                   wire:model="lastname">
                            @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            ელფოსტა <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ci-mail"></i>
                            </span>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   wire:model="email">
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">ტელეფონი</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="ci-phone"></i>
                            </span>
                            <input type="tel"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   id="phone"
                                   wire:model="phone"
                                   placeholder="+995 5__ __ __ __">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit"
                                class="btn btn-primary font-neue"
                                wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="updatePersonalInfo">
                                        <i class="bi bi-check2 me-2"></i>
                                        ცვლილებების შენახვა
                                    </span>
                            <span wire:loading wire:target="updatePersonalInfo">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        იტვირთება...
                                    </span>
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body mb-2 px-5">
                <h5 class="mb-3 font-neue" style="font-size: 16px">პაროლის შეცვლა</h5>
                @if(!auth()->user()->password)
                    <div class="alert alert-info d-flex align-items-start mb-4">
                        <i class="bi bi-info-circle fs-4 me-3 mt-1"></i>
                        <div>
                            <strong>პაროლი არ არის დაყენებული</strong>
                            <p class="mb-0 mt-1">თქვენ შემოხვედით სოციალური ანგარიშით. დააყენეთ პაროლი დამატებითი
                                უსაფრთხოებისთვის.</p>
                        </div>
                    </div>
                @endif
                <form wire:submit.prevent="updatePassword" class="row">
                    <div class="mb-3 col-12">
                        <label for="current_password" class="form-label">
                            მიმდინარე პაროლი <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               id="current_password"
                               wire:model="current_password">
                        @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6 col-sm-12">
                        <label for="new_password" class="form-label">
                            ახალი პაროლი <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               class="form-control @error('new_password') is-invalid @enderror"
                               id="new_password"
                               wire:model="new_password"
                               placeholder="მინიმუმ 8 სიმბოლო">
                        @error('new_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-md-6 col-sm-12">
                        <label for="new_password_confirmation" class="form-label">
                            პაროლის დადასტურება <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               class="form-control @error('new_password_confirmation') is-invalid @enderror"
                               id="new_password_confirmation"
                               wire:model="new_password_confirmation"
                               placeholder="გაიმეორეთ პაროლი">
                        @error('new_password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 col-12">
                        <button type="submit"
                                class="btn btn-primary font-neue"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="updatePassword">
                                <i class="bi bi-shield-check me-2"></i>
                                პაროლის შეცვლა
                            </span>
                            <span wire:loading wire:target="updatePassword">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                პაროლის შეცვლა...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('livewire.web.user.phone-verification-modal')
</div>