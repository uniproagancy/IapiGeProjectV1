@props(['sections', 'heading' => null])

@if($sections && $sections->count() > 0)
    @php
        $carouselId = 'sections-' . uniqid();
    @endphp

    <section class="container py-3 py-md-4">
        @if($heading)
            <h2 class="h5 mb-3 fw-semibold font-neue">{{ $heading }}</h2>
        @endif

        <div class="sections-carousel-wrap position-relative">
            <div class="product-swiper overflow-hidden" data-section="{{ $carouselId }}">
                <div class="swiper-wrapper">
                    @foreach($sections as $section)
                        <div class="swiper-slide" wire:key="section-{{ $section->id }}">
                            <a href="{{ route('web.section.view', $section->slug) }}"
                               class="section-card-minimal text-decoration-none">
                                <div class="section-card-minimal__img-wrap">
                                    <img src="{{ $section->image ? asset('storage/' . $section->image) : asset('web-assets/img/no-product.png') }}"
                                         alt="{{ $section->title }}"
                                         loading="lazy"
                                         class="section-card-minimal__img">
                                </div>
                                <div class="section-card-minimal__title">{{ $section->title }}</div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Navigation arrows — card-ის გვერდით, წრიული --}}
            <button type="button" class="sections-nav sections-nav--prev swiper-prev" data-section="{{ $carouselId }}">
                <i class="ci-chevron-left"></i>
            </button>
            <button type="button" class="sections-nav sections-nav--next swiper-next" data-section="{{ $carouselId }}">
                <i class="ci-chevron-right"></i>
            </button>
        </div>
    </section>

    <style>
        /* === Section Card (Minimal — ნიმუშის style) === */
        .section-card-minimal {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            padding: 18px 12px 14px;
            transition: all .2s ease;
            height: 100%;
            color: #2a2a2a;
            min-height: 150px;
        }
        .section-card-minimal:hover {
            border-color: #ff6900;
            box-shadow: 0 4px 16px rgba(255, 105, 0, 0.08);
            color: #ff6900;
        }
        .section-card-minimal__img-wrap {
            width: 100%;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .section-card-minimal__img {
            max-height: 80px;
            max-width: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
        }
        .section-card-minimal__title {
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* === Navigation arrows — წრიული, card-ის გვერდით === */
        .sections-carousel-wrap {
            padding: 0 20px;
        }
        .sections-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #e8e8e8;
            background: #fff;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            font-size: 16px;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .sections-nav--prev { left: -8px; }
        .sections-nav--next { right: -8px; }
        .sections-nav:hover {
            background: #ff6900;
            border-color: #ff6900;
            color: #fff;
        }
        .sections-nav.swiper-button-disabled {
            opacity: .3;
            cursor: not-allowed;
        }

        /* mobile */
        @media (max-width: 576px) {
            .section-card-minimal { padding: 14px 10px 12px; min-height: 130px; }
            .section-card-minimal__img-wrap { height: 65px; margin-bottom: 8px; }
            .section-card-minimal__img { max-height: 65px; }
            .section-card-minimal__title { font-size: 12px; }
            .sections-nav { width: 32px; height: 32px; font-size: 14px; }
            .sections-carousel-wrap { padding: 0 12px; }
        }
    </style>
@endif