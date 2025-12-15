<div>
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0 pb-4 pb-sm-5">
                    <div class="text-center mb-4">
                        <h3 class="h4 fw-bold mb-2 font-neue">რეგისტრაცია</h3>
                        <p class="text-muted small">შექმენით თქვენი ანგარიში</p>
                    </div>
                    <form wire:submit.prevent="register">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="register-first-name" class="form-label" style="font-size: 12px">
                                    სახელი <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-lg @error('name') is-invalid @enderror"
                                       style="font-size: 12px"
                                       id="register-first-name"
                                       wire:model="name"
                                       placeholder="სახელი">
                                @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6">
                                <label for="register-last-name" class="form-label" style="font-size: 12px">
                                    გვარი <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-lg @error('lastname') is-invalid @enderror"
                                       style="font-size: 12px"
                                       id="register-last-name"
                                       wire:model="lastname"
                                       placeholder="გვარი">
                                @error('lastname')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="register-email" class="form-label" style="font-size: 12px">
                                ელფოსტა <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   class="form-control form-control-lg @error('email') is-invalid @enderror"
                                   id="register-email"
                                   style="font-size: 12px"
                                   wire:model="email"
                                   placeholder="example@email.com">
                            @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-2">
                            <label for="register-phone" class="form-label" style="font-size: 12px">
                                ტელეფონი
                                <span class="text-danger">*</span>
                            </label>
                            <input type="tel"
                                   class="form-control form-control-lg @error('phone') is-invalid @enderror"
                                   id="register-phone"
                                   style="font-size: 12px"
                                   wire:model="phone"
                                   placeholder="+995 5__ __ __ __">
                            @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 mb-2">
                                <label for="register-password" class="form-label" style="font-size: 12px">
                                    პაროლი <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           class="form-control form-control-lg @error('password') is-invalid @enderror"
                                           id="register-password"
                                           style="font-size: 12px"
                                           wire:model="password"
                                           placeholder="მინიმუმ 8 სიმბოლო">
                                    <button type="button"
                                            class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted"
                                            onclick="togglePassword('register-password')">
                                        <i class="bi bi-eye" id="register-password-icon"></i>
                                    </button>
                                </div>
                                @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6 mb-2">
                                <label for="register-password-confirmation" class="form-label" style="font-size: 12px">
                                    პაროლის დადასტურება <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                                           id="register-password-confirmation"
                                           wire:model="password_confirmation"
                                           style="font-size: 12px"
                                           placeholder="გაიმეორეთ პაროლი">
                                    <button type="button"
                                            class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted"
                                            onclick="togglePassword('register-password-confirmation')">
                                        <i class="bi bi-eye" id="register-password-confirmation-icon"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input"
                                   type="checkbox"
                                   id="register-terms"
                                   style="font-size: 12px"
                                   wire:model="terms">
                            <label class="form-check-label small" for="register-terms" style="font-size: 12px">
                                ვეთანხმები
                                <a href="" target="_blank" class="text-primary">წესებსა და პირობებს</a>
                                და
                                <a href="" target="_blank" class="text-primary">კონფიდენციალურობის პოლიტიკას</a>
                            </label>
                            @error('terms')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit"
                                class="btn btn-primary btn-lg w-100 mb-3 font-neue"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="register">რეგისტრაცია</span>
                            <span wire:loading wire:target="register">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                მუშავდება...
                            </span>
                        </button>
                        <div class="text-center" style="font-size: 12px">
                            <span class="text-muted">უკვე გაქვთ ანგარიში?</span>
                            <a href="#"
                               class="text-primary fw-semibold text-decoration-none font-neue   "
                               data-bs-dismiss="modal"
                               data-bs-toggle="modal"
                               data-bs-target="#loginModal">
                                შესვლა
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>