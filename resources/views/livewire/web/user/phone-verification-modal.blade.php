<div wire:ignore.self class="modal fade" id="phoneVerificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-neue" style="font-size: 16px">
                    📱 ტელეფონის ნომრის დადასტურება
                </h5>
                <button type="button"
                        class="btn-close"
                        wire:click="closePhoneVerification"
                        aria-label="Close"
                        @if($showPhoneVerification) disabled @endif></button>
            </div>
            <div class="modal-body">
                <form wire:submit.prevent="verifyPhoneCode">
                    <div class="alert alert-info mb-4" role="alert">
                        <p class="mb-2 text-muted font-neue" style="font-size: 13px;">
                            დადასტურების კოდი გაიგზავნა ტელეფონზე:
                        </p>
                        <p class="fw-semibold mb-0" style="font-size: 16px;">
                            {{ $new_phone }}
                        </p>
                        <small class="text-muted d-block mt-2">⏱️ კოდი მოქმედებს 5 წუთის განმავლობაში</small>
                    </div>
                    <div class="mb-4">
                        <label for="phone_verification_code" class="form-label font-neue" style="font-size: 13px;">
                            დადასტურების კოდი
                        </label>
                        <input type="text"
                               class="form-control form-control-lg @error('phone_verification_code') is-invalid @enderror text-center"
                               id="phone_verification_code"
                               wire:model.live="phone_verification_code"
                               placeholder="0 0 0 0"
                               maxlength="7"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               required
                               style="letter-spacing: 15px; font-size: 20px; font-weight: 600; font-family: 'Courier New', monospace;">
                        @error('phone_verification_code')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    @if($phone_verification_error)
                        <div class="alert alert-danger mb-4" role="alert">
                            <i class="ci-close-circle me-2"></i>{{ $phone_verification_error }}
                        </div>
                    @endif
                    <div class="text-center mb-4">
                        @if($phone_resend_available)
                            <button type="button"
                                    class="btn btn-link btn-sm p-0 font-neue"
                                    wire:click="resendPhoneVerificationCode">
                                <i class="ci-refresh me-1"></i>კოდის ხელახლა გაგზავნა
                            </button>
                        @else
                            <small class="text-muted font-neue">
                                <i class="ci-clock me-1"></i>კოდის ხელახლა გაგზავნა {{ $phone_resend_timer }}s-ში
                            </small>
                        @endif
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <button type="submit"
                                class="btn btn-primary btn-lg font-neue"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove>
                                <i class="ci-check me-2"></i>დადასტურება
                            </span>
                            <span wire:loading>
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                შედეგს ელოდება...
                            </span>
                        </button>
                    </div>
                    <button type="button"
                            class="btn btn-outline-secondary w-100 font-neue"
                            wire:click="closePhoneVerification"
                            @if($showPhoneVerification && $phone_verification_error) disabled @endif>
                        გაუქმება
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ✅ JavaScript -->
<script>
    document.addEventListener('livewire:initialized', function () {
        console.log('Livewire initialized - Phone modal ready');

        // ✅ Open modal
        Livewire.on('openPhoneVerificationModal', () => {
            console.log('Opening phone verification modal...');
            const modal = new bootstrap.Modal(
                document.getElementById('phoneVerificationModal'),
                {
                    backdrop: true,  // ✅ Allow overlay click
                    keyboard: false  // ✅ Disable ESC key
                }
            );
            modal.show();
            setTimeout(() => {
                const input = document.getElementById('phone_verification_code');
                if (input) input.focus();
            }, 100);
        });

        // ✅ Close modal
        Livewire.on('closePhoneVerificationModal', () => {
            console.log('Closing phone verification modal...');
            const modal = bootstrap.Modal.getInstance(
                document.getElementById('phoneVerificationModal')
            );
            if (modal) modal.hide();
        });

        // ✅ Start timer
        Livewire.on('startPhoneVerificationTimer', () => {
            console.log('Starting phone verification timer...');
            startPhoneVerificationTimer();
        });

        // ✅ Handle overlay click - close modal
        const modalElement = document.getElementById('phoneVerificationModal');
        modalElement.addEventListener('click', (e) => {
            if (e.target === modalElement) {
                Livewire.call('closePhoneVerification');
            }
        });
    });

    // ✅ Format input: only 4 digits with spaces (0 0 0 0)
    document.addEventListener('input', (e) => {
        if (e.target.id === 'phone_verification_code') {
            // Remove all non-digits
            let value = e.target.value.replace(/\D/g, '');

            // Limit to 4 digits
            if (value.length > 4) {
                value = value.slice(0, 4);
            }

            // Add spaces between digits: 0 0 0 0
            e.target.value = value.split('').join(' ');

            console.log('Input value (digits only): ' + value);
        }
    });

    // ✅ 30 second countdown timer with Livewire updates
    function startPhoneVerificationTimer() {
        let timeLeft = 30;

        const timerInterval = setInterval(() => {
            timeLeft--;

            // ✅ Update Livewire every second
            Livewire.dispatch('updatePhoneResendTimer', {timeLeft: timeLeft});

            console.log('Timer: ' + timeLeft);

            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                console.log('Timer ended - enabling resend');
                Livewire.dispatch('enablePhoneResend');
            }
        }, 1000);
    }

    // ✅ Auto-focus when modal shown
    document.addEventListener('shown.bs.modal', (e) => {
        if (e.target.id === 'phoneVerificationModal') {
            const input = document.getElementById('phone_verification_code');
            if (input) {
                input.focus();
                input.select();
            }
        }
    });
</script>

<style>
    .modal-content {
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        border-bottom: 1px solid #e9ecef;
        background-color: #f8f9fa;
    }

    .alert-info {
        background-color: #e7f3ff;
        border-color: #b3d9ff;
        color: #004085;
    }

    #phone_verification_code {
        border: 2px solid #dee2e6;
        transition: all 0.3s;
    }

    #phone_verification_code:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    #phone_verification_code.is-invalid {
        border-color: #dc3545;
    }
</style>