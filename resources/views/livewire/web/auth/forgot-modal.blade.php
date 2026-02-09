<div>
    <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 px-sm-5 pt-0 pb-4 pb-sm-5">
                    <div class="text-center mb-4">
                        <h3 class="h4 fw-bold mb-2 font-neue">პაროლის აღდგენა</h3>
                        <p class="text-muted small">აღადგინეთ თქვენი პაროლი</p>
                    </div>
                    <form wire:submit.prevent="forgot">
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
                        <button type="submit"
                                class="btn btn-primary btn-lg w-100 mb-3 font-neue"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="forgot">პაროლის აღდგენა</span>
                            <span wire:loading wire:target="forgot">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                მუშავდება...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>