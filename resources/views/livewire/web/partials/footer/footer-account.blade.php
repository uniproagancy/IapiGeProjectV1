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
                    ['title' => 'მიწოდების პირობები', 'url' => '#!'],
                    ['title' => 'დაბრუნება და გაცვლა', 'url' => '#!'],
                    ['title' => 'მიწოდების ინფორმაცია', 'url' => '#!'],
                    ['title' => 'შეკვეთის თვალთვალი', 'url' => '#!'],
                    ['title' => 'გადასახადები', 'url' => '#!'],
                ];
            @endphp

            @foreach($accountLinks as $link)
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