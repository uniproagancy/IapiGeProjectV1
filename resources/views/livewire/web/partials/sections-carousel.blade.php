@props(['sections', 'heading' => null])

@if($sections && $sections->count() > 0)
    <section class="container py-4 mt-sm-3">
        @if($heading)
            <h2 class="h4 mb-3 fw-bold">{{ $heading }}</h2>
        @endif
        <div class="position-relative">
            <div class="product-swiper overflow-hidden" data-section="sections-carousel">
                <div class="swiper-wrapper">
                    @foreach($sections as $section)
                        <div class="swiper-slide" wire:key="section-{{ $section->id }}">
                            <a href="{{ route('web.section.view', $section->slug) }}"
                               class="section-card d-block text-center text-decoration-none">
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
            <div class="swiper-prev" data-section="sections-carousel">
                <i class="ci-chevron-left"></i>
            </div>
            <div class="swiper-next" data-section="sections-carousel">
                <i class="ci-chevron-right"></i>
            </div>
        </div>
    </section>
@endif