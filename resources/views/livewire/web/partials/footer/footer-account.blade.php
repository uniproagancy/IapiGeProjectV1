<div class="accordion-item col border-0">
    <h6 class="accordion-header" id="accountHeading">
        <span class="text-dark-emphasis d-none d-sm-block font-neue">ანგარიში</span>
        <button type="button"
                class="accordion-button collapsed py-3 d-sm-none"
                data-bs-toggle="collapse"
                data-bs-target="#accountLinks"
                aria-expanded="false"
                aria-controls="accountLinks">
            ანგარიში
        </button>
    </h6>
    <div class="accordion-collapse collapse d-sm-block"
         id="accountLinks"
         aria-labelledby="accountHeading"
         data-bs-parent="#footerLinks">
        <ul class="nav flex-column gap-2 pt-sm-3 pb-3 mt-n1 mb-1">
            @php
                $accountLinks = [
                    ['title' => 'თქვენი ანგარიში', 'url' => route('web.user.index')],
                    ['title' => 'შეკვეთები', 'url' => route('web.user.index', 'orders')],
                    ['title' => 'სურვილების სია', 'url' => route('web.user.index', 'wishlist')],
                    ['title' => 'ჩემი კალათა', 'url' => route('web.user.index', 'cart')],
                    ['title' => 'შეტყობინებები', 'url' => route('web.user.index', 'notifications')],
                ];
            @endphp
            @foreach($accountLinks as $link)
                @auth
                <li class="d-flex w-100 pt-1">
                    <a class="nav-link animate-underline animate-target d-inline fw-normal text-truncate p-0"
                       href="{{ $link['url'] }}">
                        {{ $link['title'] }}
                    </a>
                </li>
                @else
                    <li class="d-flex w-100 pt-1">
                        <a class="nav-link animate-underline animate-target d-inline fw-normal text-truncate p-0"
                           data-bs-toggle="modal"
                           data-bs-target="#loginModal">
                            {{ $link['title'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </div>
    <hr class="d-sm-none my-0">
</div>