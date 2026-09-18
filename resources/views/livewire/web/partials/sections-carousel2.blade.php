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
        /* ვიზუალური ენა პროდუქტის ბარათს ემთხვევა — იგივე ჩარჩო, რადიუსი და hover */
        .section-card-minimal {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #fff;
            border: 1.5px solid #eff0f2;
            border-radius: 16px;
            padding: 20px 14px 16px;
            transition: box-shadow .2s ease, transform .2s ease, border-color .2s ease, color .15s ease;
            height: 100%;
            color: #2a2a2a;
            min-height: 158px;
        }
        .section-card-minimal:hover {
            color: #ff6900;
            border-color: #e0e1e5;
            box-shadow: 0 8px 24px rgba(0,0,0,.07);
            transform: translateY(-3px);
        }
        .section-card-minimal__img-wrap {
            width: 100%;
            height: 84px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }
        .section-card-minimal__img {
            max-height: 84px;
            max-width: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
            transition: transform .25s ease;
        }
        .section-card-minimal:hover .section-card-minimal__img { transform: scale(1.05); }
        .section-card-minimal__title {
            font-size: 13px;
            font-weight: 500;
            text-align: center;
            line-height: 1.4;
            margin-top: auto;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* === Navigation arrows — წრიული, card-ის გვერდით === */
        .sections-carousel-wrap {
            padding: 0 26px;
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
            box-shadow: 0 2px 10px rgba(0,0,0,.07);
        }
        .sections-nav--prev { left: -4px; }
        .sections-nav--next { right: -4px; }
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
            .section-card-minimal { padding: 14px 10px 12px; min-height: 136px; border-radius: 14px; }
            .section-card-minimal__img-wrap { height: 65px; margin-bottom: 8px; }
            .section-card-minimal__img { max-height: 65px; }
            .section-card-minimal__title { font-size: 12px; }
            .sections-nav { width: 32px; height: 32px; font-size: 14px; }
            .sections-carousel-wrap { padding: 0 14px; }
        }
    </style>
@endif