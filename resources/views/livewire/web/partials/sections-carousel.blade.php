@props(['sections'])

@if($sections && $sections->count() > 0)
    <div class="sections-carousel-wrap position-relative mb-4">
        <div class="swiper sections-swiper">
            <div class="swiper-wrapper">
                @foreach($sections as $section)
                    <div class="swiper-slide">
                        <a href="{{ route('web.section.view', $section->id) }}"
                           class="section-card d-block text-center bg-body rounded text-decoration-none">
                            <div class="section-card-img">
                                <img src="{{ $section->image ? asset('storage/' . $section->image) : asset('web-assets/img/no-product.png') }}"
                                     alt="{{ $section->title }}" loading="lazy">
                            </div>
                            <div class="section-card-title">{{ $section->title }}</div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ნავიგაცია --}}
        <button class="sections-prev" type="button" aria-label="წინა">
            <i class="ci-chevron-left"></i>
        </button>
        <button class="sections-next" type="button" aria-label="შემდეგი">
            <i class="ci-chevron-right"></i>
        </button>
        <style>
            /* ===== Sections carousel ===== */
            .sections-carousel-wrap {
                padding: 0 44px; /* ნავიგაციის ღილაკებისთვის ადგილი */
            }

            .section-card {
                padding: 16px 10px;
                border: 1px solid #f0f0f0;
                transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
                height: 100%;
            }
            .section-card:hover {
                border-color: #ff6900;
                box-shadow: 0 8px 20px rgba(0, 0, 0, .06);
                transform: translateY(-2px);
            }

            .section-card-img {
                height: 80px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 10px;
            }
            .section-card-img img {
                max-height: 100%;
                max-width: 100%;
                object-fit: contain;
                mix-blend-mode: multiply;
            }

            .section-card-title {
                font-size: .875rem;
                font-weight: 500;
                color: #1a1a1a;
                line-height: 1.3;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .section-card:hover .section-card-title {
                color: #ff6900;
            }

            /* ნავიგაციის ღილაკები */
            .sections-prev,
            .sections-next {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                z-index: 5;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                border: 1px solid #e5e5e5;
                background: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background .2s ease, color .2s ease;
            }
            .sections-prev { left: 0; }
            .sections-next { right: 0; }
            .sections-prev:hover,
            .sections-next:hover {
                background: #ff6900;
                color: #fff;
                border-color: #ff6900;
            }
            .sections-prev.swiper-button-disabled,
            .sections-next.swiper-button-disabled {
                opacity: .35;
                cursor: default;
            }
            .swiper-slide { height: auto; }
        </style>
    </div>

    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof Swiper !== 'undefined' && document.querySelector('.sections-swiper')) {
                        new Swiper('.sections-swiper', {
                            slidesPerView: 2,
                            spaceBetween: 12,
                            navigation: {
                                nextEl: '.sections-next',
                                prevEl: '.sections-prev',
                            },
                            breakpoints: {
                                576:  { slidesPerView: 3 },
                                768:  { slidesPerView: 4 },
                                992:  { slidesPerView: 5 },
                                1200: { slidesPerView: 6 },
                            },
                        });
                    }
                });
            </script>
        @endpush
    @endonce
@endif
