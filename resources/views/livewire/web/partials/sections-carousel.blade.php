@props(['sections', 'heading' => null])

@if($sections && $sections->count() > 0)
    @php
        // უნიკალური ID — რომ ერთ გვერდზე რამდენიმე carousel-მ ერთდროულად იმუშაოს
        $carouselId = 'sections-' . uniqid();
    @endphp

    <section class="container py-3 py-md-4">
        @if($heading)
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h5 mb-0 fw-semibold font-neue">{{ $heading }}</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="section-nav-btn swiper-prev" data-section="{{ $carouselId }}">
                        <i class="ci-chevron-left"></i>
                    </button>
                    <button type="button" class="section-nav-btn swiper-next" data-section="{{ $carouselId }}">
                        <i class="ci-chevron-right"></i>
                    </button>
                </div>
            </div>
        @endif

        <div class="position-relative">
            <div class="product-swiper overflow-hidden" data-section="{{ $carouselId }}">
                <div class="swiper-wrapper">
                    @foreach($sections as $section)
                        <div class="swiper-slide" wire:key="section-{{ $section->id }}">
                            <a href="{{ route('web.section.view', $section->slug) }}"
                               class="section-card-v2 text-decoration-none">
                                <div class="section-card-v2__img-wrap">
                                    <img src="{{ $section->image ? asset('storage/' . $section->image) : asset('web-assets/img/no-product.png') }}"
                                         alt="{{ $section->title }}"
                                         loading="lazy"
                                         class="section-card-v2__img">
                                </div>
                                <div class="section-card-v2__body">
                                    <div class="section-card-v2__title">{{ $section->title }}</div>
                                    <span class="section-card-v2__arrow">
                                        <i class="ci-arrow-right"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ფარული arrows mobile-ისთვის თუ heading არ არის --}}
            @unless($heading)
                <div class="swiper-prev" data-section="{{ $carouselId }}">
                    <i class="ci-chevron-left"></i>
                </div>
                <div class="swiper-next" data-section="{{ $carouselId }}">
                    <i class="ci-chevron-right"></i>
                </div>
            @endunless
        </div>
    </section>

    <style>
        /* === Section Card v2 === */
        .section-card-v2 {
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 16px;
            overflow: hidden;
            transition: all .25s ease;
            height: 100%;
            color: inherit;
        }
        .section-card-v2:hover {
            border-color: #ff6900;
            box-shadow: 0 8px 24px rgba(255, 105, 0, 0.12);
            transform: translateY(-2px);
        }
        .section-card-v2__img-wrap {
            background: linear-gradient(180deg, #fafafa 0%, #f4f4f4 100%);
            padding: 18px;
            height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .section-card-v2__img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            transition: transform .3s ease;
        }
        .section-card-v2:hover .section-card-v2__img {
            transform: scale(1.05);
        }
        .section-card-v2__body {
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            border-top: 1px solid #f0f0f0;
        }
        .section-card-v2__title {
            font-size: 13.5px;
            font-weight: 500;
            color: #2a2a2a;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }
        .section-card-v2__arrow {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f6f6f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 14px;
            flex-shrink: 0;
            transition: all .25s ease;
        }
        .section-card-v2:hover .section-card-v2__arrow {
            background: #ff6900;
            color: #fff;
        }

        /* Navigation arrows — heading-ის გვერდით */
        .section-nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid #e0e0e0;
            background: #fff;
            color: #444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            font-size: 14px;
        }
        .section-nav-btn:hover {
            background: #ff6900;
            border-color: #ff6900;
            color: #fff;
        }
        .section-nav-btn.swiper-button-disabled {
            opacity: .35;
            cursor: not-allowed;
        }

        /* mobile */
        @media (max-width: 576px) {
            .section-card-v2__img-wrap { height: 100px; padding: 12px; }
            .section-card-v2__title { font-size: 12.5px; }
            .section-card-v2__body { padding: 10px 12px; }
        }
    </style>
@endif