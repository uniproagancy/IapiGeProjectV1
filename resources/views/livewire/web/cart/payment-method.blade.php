<div class="bg-body-tertiary rounded-4 p-4 mb-4">
    <h2 class="h5 mb-4 font-neue">გადახდის მეთოდი</h2>
    <div class="row g-3 mb-3">
        @foreach($payment_list as $payment)
            <div class="col-sm-{{ $payment->col ?? 3 }}">
                <input class="btn-check"
                       type="radio"
                       name="payment_id"
                       id="payment_{{ $payment->id }}"
                       value="{{ $payment->id }}"
                       wire:model.live="payment_id">
                <label class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4"
                       for="payment_{{ $payment->id }}"
                       style="height: 90px !important; font-size: 12px">
                    <img src="{{ asset('storage/uploads/payments/'.$payment->icon) }}" width="160">
                    <span class="fw-semibold mb-1 font-neue"
                          style="word-break: break-word; overflow-wrap: break-word; white-space: normal; line-height: 1.3; max-width: 100%">
                    {{ $payment->translations->where('locale', app()->getLocale())->first()->title ?? '' }}
                </span>
                </label>
            </div>
        @endforeach
    </div>
</div>
<style>
    .btn-check:checked + label.btn-outline-secondary {
        background-color: white;
        color: #fd5631;
        border-color: #fd5631;
    }

    .btn-check:checked + label.btn-outline-primary {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }

    .btn-check:checked + label.btn-outline-warning {
        background-color: var(--bs-warning);
        color: white;
        border-color: var(--bs-warning);
    }

    .btn-check:checked + label .text-muted {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    label.btn-outline-success,
    label.btn-outline-primary,
    label.btn-outline-warning {
        transition: all 0.2s ease;
    }

    label.btn-outline-success:hover,
    label.btn-outline-primary:hover,
    label.btn-outline-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>