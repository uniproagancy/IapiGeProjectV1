<div class="bg-body-tertiary rounded-4 p-4 mb-4" id="checkout-form">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="h5 mb-0 font-neue">მიწოდების მისამართი</h2>
        <div class="d-flex align-items-center text-muted small">
            <i class="ci-delivery me-2"></i>
            <span>უფასო მიწოდება თბილისში</span>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-12">
            <label for="address" class="form-label">
                მისამართი <span class="text-danger">*</span>
            </label>
            <div class="position-relative">
                <textarea class="form-control @error('address') is-invalid @enderror"
                          id="address"
                          rows="3"
                          wire:model="address"
                          placeholder="ქალაქი, ქუჩა, სახლის ნომერი, ბინა, სადარბაზო"></textarea>
                <div class="position-absolute top-0 end-0 mt-2 me-2">
                    <i class="ci-home text-muted"></i>
                </div>
                @error('address')
                <div class="invalid-feedback d-block">
                    <i class="ci-info-circle me-1"></i>{{ $message }}
                </div>
                @enderror
            </div>
            <div class="form-text">
                <i class="ci-info-circle me-1"></i>
                გთხოვთ მიუთითოთ ზუსტი მისამართი სწრაფი მიწოდებისთვის
            </div>
        </div>
        <div class="col-12">
            <label for="comment" class="form-label d-flex align-items-center">
                დამატებითი კომენტარი
                <span class="badge bg-secondary ms-2 small">არასავალდებულო</span>
            </label>
            <div class="position-relative">
                <textarea class="form-control"
                          id="comment"
                          rows="2"
                          wire:model="comment"
                          placeholder="მაგ: დარეკეთ ჩამოსვლამდე, კოდი 25, მე-3 სართული"></textarea>
                <div class="position-absolute top-0 end-0 mt-2 me-2">
                    <i class="ci-message-square text-muted"></i>
                </div>
            </div>
            <div class="form-text">
                კომენტარი კურიერისთვის (სადარბაზოს კოდი, საკონტაქტო ინფორმაცია და ა.შ.)
            </div>
        </div>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-md-6">
            <div class="border rounded-3 p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="d-flex justify-content-center align-items-center"
                             style="width: 40px; height: 40px; border-radius: 20px">
                            <i class="ci-clock text-success fs-5"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="fw-semibold small font-neue">სწრაფი მიწოდება</div>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            თბილისში - 1-2 სამუშაო დღე<br>
                            რეგიონში - 3-5 სამუშაო დღე
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded-3 p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="d-flex justify-content-center align-items-center"
                             style="width: 40px; height: 40px; border-radius: 20px">
                            <i class="ci-phone text-primary fs-5"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="fw-semibold small font-neue">დაგიკავშირდებით</div>
                        <div class="text-muted" style="font-size: 0.8rem;">
                            კურიერი დაგირეკავთ<br>
                            გაგზავნამდე
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Script for scroll to error -->
<script>
    // Listen for scrollToError event
    document.addEventListener('livewire:navigated', function() {
        Livewire.on('scrollToError', ({ field }) => {
            scrollToErrorField(field);
        });
    });

    /**
     * ✅ Scroll to error field with smooth animation
     */
    function scrollToErrorField(fieldName) {
        console.log('📍 Scrolling to error field:', fieldName);

        // Map field names to HTML IDs
        const fieldMap = {
            'city_id': 'city_id',
            'address': 'address',
            'comment': 'comment',
            'payment_id': 'payment_method',
        };

        const elementId = fieldMap[fieldName] || fieldName;
        const element = document.getElementById(elementId);

        if (element) {
            // ✅ Scroll with smooth behavior
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            // ✅ Add focus and highlight animation
            element.focus({ preventScroll: true });
            element.classList.add('field-error-highlight');

            // ✅ Remove highlight after 3 seconds
            setTimeout(() => {
                element.classList.remove('field-error-highlight');
            }, 3000);

            console.log('✅ Scrolled to:', fieldName);
        } else {
            console.warn('⚠️  Field not found:', fieldName);
            // Fallback: scroll to form top
            const form = document.getElementById('checkout-form');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }

    // ✅ Initial event listener setup
    document.addEventListener('DOMContentLoaded', function() {
        Livewire.on('scrollToError', ({ field }) => {
            scrollToErrorField(field);
        });
    });
</script>

<style>
    .form-check-custom {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .form-check-custom:hover {
        border-color: var(--bs-primary) !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    /* ✅ Highlight error field */
    .field-error-highlight {
        background-color: rgba(220, 53, 69, 0.1) !important;
        border-color: #dc3545 !important;
        animation: pulse-error 0.6s ease-in-out;
    }

    @keyframes pulse-error {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }
    }

    /* ✅ Invalid input styling */
    .form-control.is-invalid,
    .form-select.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='12 12 24 24'%3e%3ccircle cx='24' cy='24' r='11' fill='none' stroke='%23dc3545' stroke-width='2'/%3e%3cpath fill='%23dc3545' d='M24 16v8M24 28a1 1 0 1 1 0-2 1 1 0 0 1 0 2z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(1.5em + 0.75rem) calc(1.5em + 0.75rem);
    }

    .invalid-feedback {
        display: none;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    .form-control.is-invalid ~ .invalid-feedback,
    .form-select.is-invalid ~ .invalid-feedback {
        display: block;
    }
</style>