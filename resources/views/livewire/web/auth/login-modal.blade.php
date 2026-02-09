<div>
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0 pb-4 pb-sm-5">
                    <div class="text-center mb-4">
                        <h3 class="h4 fw-bold mb-2 font-neue">შესვლა</h3>
                        <p class="text-muted small">გაიარეთ ავტორიზაცია რომ ისარგებლოთ მეტი ფუნქციონალით</p>
                    </div>
                    <form wire:submit.prevent="login">
                        <div class="mb-2">
                            <label for="login-email" class="form-label" style="font-size: 12px">ელ-ფოსტა</label>
                            <input type="email"
                                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                                   id="login-email"
                                   style="font-size: 12px"
                                   wire:model="email"
                                   placeholder="example@email.com">
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="login-password" class="form-label mb-0"
                                       style="font-size: 12px">პაროლი</label>
                                <a href="#"
                                   style="font-size: 12px"
                                   class="small text-primary text-decoration-none"
                                   data-bs-dismiss="modal"
                                   data-bs-toggle="modal"
                                   data-bs-target="#forgotModal">
                                    დაგავიწყდათ პაროლი?
                                </a>
                            </div>
                            <input type="password"
                                   style="font-size: 12px"
                                   class="form-control form-control-lg @error('password') is-invalid @enderror"
                                   id="login-password"
                                   wire:model="password"
                                   placeholder="••••••••">
                            @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="remember"
                                   wire:model="remember">
                            <label class="form-check-label" for="remember">
                                დამიმახსოვრე
                            </label>
                        </div>
                        <button type="submit"
                                class="btn btn-primary btn-lg w-100 mb-3 font-neue"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="login">შესვლა</span>
                            <span wire:loading wire:target="login">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                იტვირთება...
                            </span>
                        </button>
                        <div class="text-center" style="font-size: 12px">
                            <span class="text-muted">არ გაქვთ ანგარიში?</span>
                            <a href="#"
                               class="text-primary fw-semibold text-decoration-none font-neue"
                               data-bs-dismiss="modal"
                               data-bs-toggle="modal"
                               data-bs-target="#registerModal">
                                რეგისტრაცია
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>