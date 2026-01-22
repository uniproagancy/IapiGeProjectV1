@section('page_css')
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/pages/authentication.css') }}">
@endsection

<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <div class="auth-wrapper auth-basic px-2">
                <div class="auth-inner my-2">
                    <div class="card mb-0">
                        <div class="card-body">
                            <h4 class="card-title mb-1">პაროლის განახლება 🔒</h4>
                            <p class="card-text mb-2">გთხოვთ შეიყვანოთ სანდო პაროლი და არავის არ გაუმხილოთ ის!</p>
                            <form class="auth-login-form mt-2" wire:submit.prevent="resetPassword">
                                <div class="mb-1">
                                    <label for="email" class="form-label">პაროლი</label>
                                    <input type="password" class="form-control @error('password') border-danger is-invalid @enderror" id="password" name="password" wire:model="password" />
                                    @error('password') <span class="error-message text-danger" style="font-size: 12px">{{ $message }}</span> @enderror
                                </div>
                                <div class="mb-1">
                                    <label for="password_confirmation" class="form-label">გაიმეორეთ პაროლი</label>
                                    <input type="password" class="form-control @error('password_confirmation') border-danger is-invalid @enderror" id="password_confirmation" name="password_confirmation" wire:model="password_confirmation" />
                                    @error('password_confirmation') <span class="error-message text-danger" style="font-size: 12px">{{ $message }}</span> @enderror
                                </div>
                                <input type="hidden" value="{{ request()->value }}" name="value" wire:model="value  ">
                                <button class="btn btn-primary w-100 reset-password-button" tabindex="2">პაროლის განახლება</button>
                            </form>
                        </div>
                        <div wire:loading.flex wire:target="resetPassword" wire:loading.class.remove="d-none" class="d-none position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-25 d-flex justify-content-center align-items-center rounded">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
