<div class="accordion-item col border-0">
    <h6 class="accordion-header" id="customerHeading">
        <span class="text-dark-emphasis d-none d-sm-block font-neue">მომხმარებლის სერვისი</span>
        <button type="button"
                class="accordion-button collapsed py-3 d-sm-none"
                data-bs-toggle="collapse"
                data-bs-target="#customerLinks"
                aria-expanded="false"
                aria-controls="customerLinks">
            მომხმარებლის სერვისი
        </button>
    </h6>
    <div class="accordion-collapse collapse d-sm-block"
         id="customerLinks"
         aria-labelledby="customerHeading"
         data-bs-parent="#footerLinks">
        <ul class="nav flex-column gap-2 pt-sm-3 pb-3 mt-n1 mb-1">
            @php
                $serviceLinks = [
                    ['title' => 'წესები და პირობები', 'url' => '/static/rules'],
                    ['title' => 'მიწოდების პირობები', 'url' => '/static/shipping-policy'],
                    ['title' => 'დაბრუნების პოლიტიკა', 'url' => '/static/return-policy'],
                ];
            @endphp
            @foreach($serviceLinks as $link)
                <li class="d-flex w-100 pt-1">
                    <a class="nav-link animate-underline animate-target d-inline fw-normal text-truncate p-0"
                       href="{{ $link['url'] }}">
                        {{ $link['title'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    <hr class="d-sm-none my-0">
</div>