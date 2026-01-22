<button type="button"
        class="btn btn-icon btn-lg btn-secondary position-relative rounded-circle ms-2"
        data-bs-toggle="offcanvas"
        data-bs-target="#shoppingCart"
        aria-controls="shoppingCart"
        aria-label="კალათა">
    @if($cartCount > 0)
        <span class="position-absolute top-0 start-100 mt-n1 ms-n3 badge text-bg-success border border-3 border-dark rounded-pill"
              style="--cz-badge-padding-y: .25em; --cz-badge-padding-x: .42em">
            {{ $cartCount }}
        </span>
    @endif
    <span class="position-absolute top-0 start-0 d-flex align-items-center justify-content-center w-100 h-100 rounded-circle animate-slide-end fs-lg">
        <i class="ci-shopping-cart animate-target ms-n1"></i>
    </span>
</button>