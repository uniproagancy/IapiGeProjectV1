<div>
    <div wire:ignore.self class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 font-neue" id="checkoutModalLabel" style="font-size: 16px">
                        სწრაფი შეძენა
                    </h1>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                            onclick="closeModalProperly()">
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="placeOrder">
                        <div class="section-title mb-3">
                            <h5 class="font-neue mb-0" style="font-size: 15px">პირადი ინფორმაცია</h5>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_name" class="form-label font-neue" style="font-size: 12px">
                                    სახელი <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="modal_name"
                                       wire:model.blur="name"
                                       required>
                                @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_lastname" class="form-label font-neue" style="font-size: 12px">
                                    გვარი <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('lastname') is-invalid @enderror"
                                       id="modal_lastname"
                                       wire:model.blur="lastname"
                                       required>
                                @error('lastname')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_email" class="form-label font-neue" style="font-size: 12px">
                                    ელ-ფოსტა <span class="text-danger">*</span>
                                </label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="modal_email"
                                       wire:model.blur="email"
                                       placeholder="მაგ: example@gmail.com"
                                       required>
                                @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_phone" class="form-label font-neue" style="font-size: 12px">
                                    ტელეფონი <span class="text-danger">*</span>
                                </label>
                                <input type="tel"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       id="modal_phone"
                                       wire:model.blur="phone"
                                       placeholder="მაგ: 555 123456"
                                       required>
                                @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_address" class="form-label font-neue" style="font-size: 12px">
                                მისამართი <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control @error('address') is-invalid @enderror"
                                   id="modal_address"
                                   wire:model.blur="address"
                                   placeholder="მაგ: თბილისი, ვაკე, მეცხოვრის ქ. 1"
                                   required>
                            @error('address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="modal_comment" class="form-label font-neue" style="font-size: 12px">
                                კომენტარი (არასავალდებულო)
                            </label>
                            <textarea class="form-control"
                                      id="modal_comment"
                                      wire:model.blur="comment"
                                      rows="2"
                                      placeholder="დამატებითი ინფორმაცია..."></textarea>
                        </div>
                        <div class="section-title mb-3 mt-4">
                            <h5 class="font-neue mb-0" style="font-size: 15px">გადახდის მეთოდი</h5>
                        </div>
                        <div class="row g-3 mb-3">
                            @foreach($payment_list as $payment)
                                <div class="col-sm-{{ $payment->col ?? 3 }}">
                                    <input class="btn-check"
                                           type="radio"
                                           name="payment_id"
                                           id="modal_payment_{{ $payment->id }}"
                                           value="{{ $payment->id }}"
                                           wire:model.live="payment_id"
                                           required>
                                    <label class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4"
                                           for="modal_payment_{{ $payment->id }}"
                                           style="height: 90px !important; font-size: 12px">
                                        <img src="{{ asset('storage/uploads/payments/'.$payment->icon) }}"
                                             @if($payment->id === 1 OR $payment->id === 2) width="45" @else width="160" @endif
                                             alt="{{ $payment->translations->where('locale', app()->getLocale())->first()->title ?? '' }}">
                                        <span class="fw-semibold mb-1 font-neue"
                                              style="word-break: break-word; overflow-wrap: break-word; white-space: normal; line-height: 1.3; max-width: 100%">
                        {{ $payment->translations->where('locale', app()->getLocale())->first()->title ?? '' }}
                    </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('payment_id')
                        <div class="alert alert-danger mb-3">{{ $message }}</div>
                        @enderror

                        <!-- ✅ Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit"
                                    class="btn btn-lg btn-primary font-neue"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75">
                            <span wire:loading.remove>
                                <i class="ci-check me-2"></i>გადახდა
                            </span>
                                <span wire:loading>
                                <span class="spinner-border spinner-border-sm me-2" role="status"
                                      aria-hidden="true"></span>
                                შედეგს ელოდება...
                            </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Styles -->
    <style>
        .section-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .payment-option {
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            background-color: #f9f9f9 !important;
            border-color: #007bff !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            display: block !important;
            font-size: 0.875rem;
        }

        /* ✅ Better button styling -->
        .btn-check:checked + label.btn-outline-secondary {
            background-color: white;
            color: #fd5631;
            border-color: #fd5631;
        }

        label.btn-outline-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
</style>

    <!-- ✅ Script for modal events -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Listen for modal open events
            Livewire.on('openCheckoutModal', () => {
                const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                modal.show();
            });

            // Listen for modal close events
            Livewire.on('closeCheckoutModal', () => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                if (modal) modal.hide();
            });

            // Scroll to error field in modal
            Livewire.on('scrollToError', ({ field }) => {
                scrollToErrorInModal(field);
            });
        });

        /**
         * ✅ Scroll to error field in modal
         */
        function scrollToErrorInModal(fieldName) {
            const fieldMap = {
                'name': 'modal_name',
                'lastname': 'modal_lastname',
                'email': 'modal_email',
                'phone': 'modal_phone',
                'address': 'modal_address',
                'comment': 'modal_comment',
                'payment_id': 'modal_payment_method',
            };

            const elementId = fieldMap[fieldName] || `modal_${fieldName}`;
            const element = document.getElementById(elementId);

            if (element) {
                // Scroll the modal body to the element
                const modalBody = document.querySelector('.modal-body');
                if (modalBody) {
                    const elementRect = element.getBoundingClientRect();
                    const containerRect = modalBody.getBoundingClientRect();

                    const scrollTop = elementRect.top - containerRect.top + modalBody.scrollTop - 100;
                    modalBody.scrollTop = scrollTop;
                }

                // Highlight the field
                element.focus();
                element.classList.add('field-error-highlight');

                setTimeout(() => {
                    element.classList.remove('field-error-highlight');
                }, 3000);

                console.log('✅ Scrolled to:', fieldName);
            }
        }
    </script>
    <style>
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
    </style>
    <script>
        function closeModalProperly() {
            const modalElement = document.getElementById('checkoutModal');
            const modal = bootstrap.Modal.getInstance(modalElement);

            if (modal) {
                modal.hide();
            }

            // ✅ backdrop-ის წაშლა
            setTimeout(() => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }, 300);
        }

        document.addEventListener('livewire:initialized', () => {
            const modalElement = document.getElementById('checkoutModal');

            // ✅ როცა modal დახურულია, backdrop წაიშალა
            modalElement.addEventListener('hidden.bs.modal', () => {
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            });

            Livewire.on('openCheckoutModal', () => {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            });

            Livewire.on('closeCheckoutModal', () => {
                closeModalProperly();
            });

            Livewire.on('scrollToError', ({ field }) => {
                scrollToErrorInModal(field);
            });
        });

        function scrollToErrorInModal(fieldName) {
            const fieldMap = {
                'name': 'modal_name',
                'lastname': 'modal_lastname',
                'email': 'modal_email',
                'phone': 'modal_phone',
                'address': 'modal_address',
                'comment': 'modal_comment',
                'payment_id': 'modal_payment_method',
            };

            const elementId = fieldMap[fieldName] || `modal_${fieldName}`;
            const element = document.getElementById(elementId);

            if (element) {
                const modalBody = document.querySelector('.modal-body');
                if (modalBody) {
                    const elementRect = element.getBoundingClientRect();
                    const containerRect = modalBody.getBoundingClientRect();
                    const scrollTop = elementRect.top - containerRect.top + modalBody.scrollTop - 100;
                    modalBody.scrollTop = scrollTop;
                }

                element.focus();
                element.classList.add('field-error-highlight');

                setTimeout(() => {
                    element.classList.remove('field-error-highlight');
                }, 3000);
            }
        }
    </script>
</div>