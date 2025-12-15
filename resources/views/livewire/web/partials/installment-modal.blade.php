<div class="modal fade" id="installmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title font-neue">განვადება</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex flex-column gap-3">
                    <!-- Option 1 -->
                    <label class="border rounded px-3 py-1 cursor-pointer" style="cursor: pointer;">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="installment" value="georgian_bank" class="form-check-input">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-1">🦊</span>
                                <span class="text-body-emphasis font-neue" style="font-size: 13px">საქართველოს ბანკი</span>
                            </div>
                        </div>
                    </label>
                    <label class="border rounded px-3 py-1 cursor-pointer" style="cursor: pointer;">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="installment" value="credit_bank" class="form-check-input">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-1">🌍</span>
                                <span class="text-body-emphasis font-neue" style="font-size: 13px">კრედიტო ბანკი</span>
                            </div>
                        </div>
                    </label>
                    <label class="border rounded px-3 py-1 cursor-pointer" style="cursor: pointer;">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" name="installment" value="ametebi_bank" class="form-check-input">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fs-1">⚠️</span>
                                <span class="text-body-emphasis font-neue" style="font-size: 13px">ამეთან ბანკი</span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <div class="modal-footer border-0 gap-2">
                <button type="button" class="btn btn-outline-secondary font-neue" data-bs-dismiss="modal">დახურვა</button>
                <button type="button" class="btn btn-primary font-neue" onclick="selectInstallment()">გადასვლა</button>
            </div>
        </div>
    </div>
</div>
<style>
    label.border {
        transition: all 0.2s ease;
    }

    label.border:hover {
        background-color: #f8f9fa;
        border-color: #6f42c1 !important;
    }

    label.border input[type="radio"]:checked {
        border-color: #6f42c1;
    }

    label.border input[type="radio"]:checked ~ .d-flex {
        color: #6f42c1;
    }
</style>

<script>
    // Open modal on button click
    document.getElementById('installmentBtn')?.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('installmentModal'));
        modal.show();
    });

    // Select installment and redirect to checkout
    function selectInstallment() {
        const selected = document.querySelector('input[name="installment"]:checked');

        if (!selected) {
            alert('აირჩიეთ განვადების ვარიანტი');
            return;
        }

        const installmentType = selected.value;
        {{--const checkoutUrl = `{{ route('web.checkout.index') }}?installment=${installmentType}`;--}}

        window.location.href = checkoutUrl;
    }
</script>