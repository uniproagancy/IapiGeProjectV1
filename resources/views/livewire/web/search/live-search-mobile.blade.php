<div class="position-relative" x-data="{ open: @entangle('isOpen') }" @click.outside="$wire.closeSearch()">
    <div class="position-relative">
        <i class="ci-search position-absolute top-50 start-0 translate-middle-y d-flex fs-lg text-white ms-3"
           style="z-index: 5; pointer-events: none;"></i>

        <input type="search"
               class="form-control form-icon-start border-white rounded-pill"
               placeholder="მოძებნე სასურველი პროდუქტი"
               wire:model.live.debounce.300ms="query"
               @focus="$wire.set('isOpen', true)"
               data-autofocus="collapse"
               autocomplete="off"
               style="font-size: 14px; padding-left: 2.8rem;">

        @if($query)
            <button type="button"
                    class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-white-50 p-0 me-3"
                    wire:click="$set('query', '')"
                    style="z-index: 5;">
                <i class="ci-close fs-base"></i>
            </button>
        @endif
    </div>
    @if($isOpen && strlen($query) >= 2)
        <div class="position-absolute top-100 start-0 w-100 bg-white shadow-lg rounded-3 mt-2 overflow-hidden"
             style="max-height: 400px; z-index: 1050;"
             x-show="open"
             x-transition>

            @if($this->results->count() > 0)
                <div class="overflow-auto" style="max-height: 400px;">
                    @foreach($this->results as $product)
                        @php
                            $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');
                        @endphp

                        <div class="search-item d-flex align-items-center p-2 border-bottom"
                             style="cursor: pointer;"
                             wire:key="search-mobile-{{ $product->id }}"
                             wire:click="selectProduct('{{ $translation->slug }}')">

                            <img src="{{ asset('storage/' . $product->main_image) }}"
                                 alt="{{ $translation->title }}"
                                 class="rounded"
                                 width="50"
                                 height="50"
                                 style="object-fit: cover;"
                                 loading="lazy">

                            <div class="ms-2 flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate small">
                                    {{ $translation->title }}
                                </div>
                                <div class="fw-bold small">
                                    @if(!empty($product->price->discount_price))
                                        <span class="text-primary">{{ number_format($product->price->discount_price, 2) }} ₾</span>
                                        <span class="text-muted text-decoration-line-through ms-1">{{ number_format($product->price->regular_price, 2) }}</span>
                                    @else
                                        <span class="text-dark">{{ number_format($product->price->regular_price, 2) }} ₾</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($this->results->count() >= 10)
                    <div class="border-top">
                        <a href="{{ route('web.products.index', ['search' => $query]) }}"
                           class="d-block text-center py-2 text-decoration-none fw-semibold small"
                           wire:navigate
                           wire:click="closeSearch">
                            ყველა შედეგის ნახვა
                        </a>
                    </div>
                @endif
            @else
                <div class="p-3 text-center">
                    <p class="text-muted mb-2 small">ვერაფერი მოიძებნა</p>
                    <a href="{{ route('web.products.index') }}"
                       class="btn btn-sm btn-outline-primary"
                       wire:navigate
                       wire:click="closeSearch">
                        ყველა პროდუქტი
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>