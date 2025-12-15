<div class="accordion-item col border-0">
    <h6 class="accordion-header" id="companyHeading">
        <span class="text-dark-emphasis d-none d-sm-block font-neue">კომპანია</span>
        <button type="button"
                class="accordion-button collapsed py-3 d-sm-none"
                data-bs-toggle="collapse"
                data-bs-target="#companyLinks"
                aria-expanded="false"
                aria-controls="companyLinks">
            კომპანია
        </button>
    </h6>
    <div class="accordion-collapse collapse d-sm-block"
         id="companyLinks"
         aria-labelledby="companyHeading"
         data-bs-parent="#footerLinks">
        <ul class="nav flex-column gap-2 pt-sm-3 pb-3 mt-n1 mb-1">
            @php
                $companyLinks = [
                    ['title' => 'ჩვენ შესახებ', 'url' => '#!'],
                    ['title' => 'ჩვენი გუნდი', 'url' => '#!'],
                    ['title' => 'კარიერა', 'url' => '#!'],
                    ['title' => 'დაგვიკავშირდით', 'url' => '#!'],
                    ['title' => 'სიახლები', 'url' => '#!'],
                ];
            @endphp

            @foreach($companyLinks as $link)
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