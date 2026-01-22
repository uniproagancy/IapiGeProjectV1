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
                            <h4 class="card-title mb-1">პაროლის აღდგენა 🔒</h4>
                            <p class="card-text mb-2">პაროლის აღსადგენად დაგჭირდებათ თქვენი ელ-ფოსტა!</p>
                            <form class="auth-login-form mt-2" wire:submit.prevent="forgotPassword">
                                <div class="mb-1">
                                    <label for="email" class="form-label">ელ-ფოსტა</label>
                                    <input type="text" class="form-control @error('email') border-danger is-invalid @enderror" id="email" name="email" wire:model="email" />
                                    @error('email') <span class="error-message text-danger" style="font-size: 12px">{{ $message }}</span> @enderror
                                </div>
                                <button type="submit" class="btn btn-primary w-100 forgot-password-button"
                                        wire:loading.attr="disabled"
                                        wire:target="sendResetLink">პაროლის აღდგენა</button>
                            </form>
                            <p class="text-center mt-2">
                                <a href="{{ route('dashboard.login') }}">
                                    <i data-feather="chevron-left"></i> ავტორიზაციაზე დაბრუნება
                                </a>
                            </p>
                        </div>
                        <div
                                wire:loading.flex
                                wire:target="sendResetLink"
                                wire:loading.class.remove="d-none"
                                class="d-none position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-25 d-flex justify-content-center align-items-center rounded">
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