@section('page_css')
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/pages/authentication.css') }}">
@endsection

<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-body">
            <div class="auth-wrapper auth-basic px-2">
                <div class="auth-inner my-2">
                    <div class="card mb-0">
                        <div class="card-body">
                            <h4 class="card-title mb-1">მოგესალმებით 🔒</h4>
                            <p class="card-text mb-2">ავტორიზაციის გასავლელად გთხოვთ გამოიყენოთ თქვენი ელ-ფოსტა და პაროლი!</p>
                            <form class="auth-login-form mt-2" wire:submit.prevent="login">
                                <div class="mb-1">
                                    <label class="form-label">ელ-ფოსტა</label>
                                    <input type="email" wire:model="email" class="form-control @error('email') border-danger is-invalid @enderror">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="mb-1">
                                    <div class="d-flex justify-content-between">
                                        <label class="form-label" for="password">პაროლი</label>
                                        <a href="{{ route('dashboard.password.forgot') }}">
                                            <small>დაგავიწყდა პაროლი?</small>
                                        </a>
                                    </div>
                                    <div class="mb-1">
                                        <input type="password" class="form-control @error('password') border-danger is-invalid @enderror" id="password" name="password" wire:model="password"/>
                                        @error('password') <span class="error-message text-danger" style="font-size: 12px">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="mb-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me" wire:model="remember_me" />
                                        <label class="form-check-label" for="remember_me"> დამახსოვრება </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" tabindex="4">ავტორიზაცია</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>