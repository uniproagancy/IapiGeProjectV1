<footer style="margin-top: 50px; background: #0f1117; border-top: 4px solid #ff6900;">
    <div class="container position-relative z-1" data-bs-theme="dark">

        {{-- მთავარი footer სექცია --}}
        <div class="row py-5 g-4">

            {{-- Brand / კომპანია --}}
            <div class="col-lg-3 col-md-6">
                <a href="{{ route('web.main.index') }}"
                   class="d-inline-block text-white text-decoration-none fw-bold fs-4 mb-3">
                    IAPI.GE
                </a>
                <div class="d-flex gap-2">
                    <a href="https://www.instagram.com/iapi_ge/"
                       class="footer-social-btn" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                        <i class="ci-instagram"></i>
                    </a>
                    <a href="https://www.facebook.com/profile.php?id=100091503274684"
                       class="footer-social-btn" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <i class="ci-facebook"></i>
                    </a>
                    <a href="https://www.tiktok.com/@iapi.ge"
                       class="footer-social-btn" aria-label="TikTok" target="_blank" rel="noopener noreferrer">
                        <i class="ci-tiktok"></i>
                    </a>
                </div>
            </div>

            {{-- კომპანია --}}
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-white fw-semibold mb-3 text-uppercase" style="font-size: 12px; letter-spacing: .8px;">
                    კომპანია
                </h6>
                <ul class="list-unstyled d-flex flex-column gap-2 m-0">
                    @foreach([
                        ['title' => 'ჩვენ შესახებ', 'url' => '/about-us'],
                        ['title' => 'კონტაქტი',     'url' => '/contact'],
                    ] as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="footer-link">{{ $link['title'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- ანგარიში --}}
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-white fw-semibold mb-3 text-uppercase" style="font-size: 12px; letter-spacing: .8px;">
                    ანგარიში
                </h6>
                <ul class="list-unstyled d-flex flex-column gap-2 m-0">
                    @foreach([
                        ['title' => 'ჩემი შეკვეთები', 'url' => route('web.user.index', ['page' => 'orders'])],
                        ['title' => 'სურვილების სია', 'url' => route('web.user.index', ['page' => 'wishlist'])],
                    ] as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="footer-link">{{ $link['title'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- მომხმარებელი --}}
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="text-white fw-semibold mb-3 text-uppercase" style="font-size: 12px; letter-spacing: .8px;">
                    მომხმარებელი
                </h6>
                <ul class="list-unstyled d-flex flex-column gap-2 m-0">
                    @foreach([
                        ['title' => 'წესები და პირობები',  'url' => '/static/rules'],
                        ['title' => 'უსაფრთხოების პოლიტიკა', 'url' => '/static/privacy-policy'],
                        ['title' => 'მიწოდების პირობები',  'url' => '/static/delivery'],
                        ['title' => 'დაბრუნების პოლიტიკა',  'url' => '/static/return'],
                    ] as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="footer-link">{{ $link['title'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- კონტაქტი --}}
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-semibold mb-3 text-uppercase" style="font-size: 12px; letter-spacing: .8px;">
                    კონტაქტი
                </h6>
                <ul class="list-unstyled d-flex flex-column gap-3 m-0">
                    <li class="d-flex align-items-start gap-2">
                        <i class="ci-map-pin text-secondary mt-1" style="font-size: 15px; flex-shrink:0;"></i>
                        <span class="text-secondary fs-sm">ქ. თბილისი, შარტავას ქ. №3</span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="ci-phone text-secondary" style="font-size: 15px; flex-shrink:0;"></i>
                        <a href="tel:+995555700720" class="footer-link">+995 555 700 720</a>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="ci-mail text-secondary" style="font-size: 15px; flex-shrink:0;"></i>
                        <a href="mailto:info@iapi.ge" class="footer-link">info@iapi.ge</a>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="ci-copy text-secondary" style="font-size: 15px; flex-shrink:0;"></i>
                        <span class="text-secondary fs-sm">შპს „უნიპრო"</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Divider --}}
        <div style="height: 1px; background: rgba(255,255,255,0.08);"></div>

        {{-- Copyright --}}
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between py-4 gap-3">
            <p class="text-secondary fs-xs mb-0 order-2 order-md-1">
                &copy; {{ date('Y') }} IAPI.GE — ყველა უფლება დაცულია.
            </p>
            <div class="d-flex align-items-center gap-3 order-1 order-md-2">
                <img src="{{ asset('web-assets/img/visa-dark-mode.svg') }}" alt="Visa" style="height: 22px; opacity: .7;">
                <img src="{{ asset('web-assets/img/mastercard.svg') }}" alt="Mastercard" style="height: 22px; opacity: .7;">
            </div>
        </div>

    </div>
</footer>

@include('livewire.web.partials.scroll-to-top')

<style>
    .footer-link {
        color: #8b92a5;
        text-decoration: none;
        font-size: 14px;
        transition: color .15s ease;
    }
    .footer-link:hover { color: #ff6900; }

    .footer-social-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #8b92a5;
        text-decoration: none;
        font-size: 16px;
        transition: background .15s ease, color .15s ease, border-color .15s ease;
    }
    .footer-social-btn:hover {
        background: #ff6900;
        border-color: #ff6900;
        color: #fff;
    }
</style>