<div class="position-relative"
     x-data="{
         open: @entangle('isOpen'),
         handleKeydown(e) {
             if (!this.open) return;
             if (e.key === 'ArrowDown') {
                 e.preventDefault();
                 $wire.navigateDown();
             } else if (e.key === 'ArrowUp') {
                 e.preventDefault();
                 $wire.navigateUp();
             } else if (e.key === 'Enter') {
                 e.preventDefault();
                 $wire.selectCurrent();
             } else if (e.key === 'Escape') {
                 $wire.closeSearch();
             }
         }
     }"
     @click.outside="$wire.closeSearch()"
     @keydown.window="handleKeydown($event)">
    <div class="position-relative">
        <i class="ci-search position-absolute top-50 start-0 translate-middle-y d-flex fs-lg text-white ms-3"
           style="z-index: 5; pointer-events: none;"></i>
        <input type="search"
               class="form-control form-control-lg form-icon-start border-white rounded-pill pe-5"
               placeholder="{{ trans('trans.search_your_product') }}"
               wire:model.live.debounce.300ms="query"
               @focus="$wire.set('isOpen', true)"
               autocomplete="off"
               style="font-size: 14px; padding-left: 2.8rem; background: white; border-radius: 10px !important; color: #252525">
        @if($query)
            <button type="button"
                    class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-white-50 p-0 me-3"
                    wire:click="$set('query', '')"
                    style="z-index: 5;">
                <i class="ci-close fs-base"></i>
            </button>
        @endif
    </div>

    <!-- Search Results Dropdown -->
    @if($isOpen && strlen($query) >= 2)
        <div class="position-absolute top-100 start-0 w-100 bg-white shadow-lg rounded-3 mt-2 overflow-hidden px-3 p-2"
             style="max-height: 470px; z-index: 1050;"
             x-show="open"
             x-transition>
            @if($this->results->count() > 0)
                <div class="overflow-auto search-item-container" style="max-height: 400px;">
                    @foreach($this->results as $index => $product)
                        @php
                            $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');
                        @endphp
                        <div class="search-item d-flex align-items-center px-3 py-2 {{ $selectedIndex === $index ? 'bg-light' : '' }}"
                             style="cursor: pointer; transition: background-color 0.15s ease; border-bottom: solid 1px #d1d1d1"
                             wire:key="search-result-{{ $product->id }}"
                             wire:click="selectProduct('{{ $translation->slug }}')"
                             @mouseenter="$wire.selectedIndex = {{ $index }}">
                            <div class="flex-shrink-0 rounded overflow-hidden" style="border: solid 1px #d1d1d1;">
                                <img src="{{ asset('storage/' . $product->main_image) }}"
                                     alt="{{ $translation->title }}"
                                     width="50"
                                     height="50"
                                     style="object-fit: cover;"
                                     loading="lazy">
                            </div>
                            <div class="flex-grow-1 ms-3 min-w-0">
                                <div class="fw-semibold text-truncate font-neue"
                                     style="font-size: 15px; padding: 0 0 0 3px">
                                    {{ $translation->title }}
                                </div>
                                @if($product->id)
                                    <div class="text-muted small" style="padding: 0 0 0 3px; font-size: 11px">
                                        SKU: {{ $product->id }}
                                    </div>
                                @endif
                                <div class="d-flex align-items-center gap-2" style="padding: 0 0 0 3px">
                                    @if(!empty($product->price->discount_price))
                                        <span class="fw-bold text-primary" style="font-size: 14px">
                                            {{ number_format($product->price->discount_price, 2) }} ₾
                                        </span>
                                        <span class="text-decoration-line-through text-muted" style="font-size: 12px">
                                            {{ number_format($product->price->regular_price, 2) }} ₾
                                        </span>
                                        @if($product->price->discount_percent)
                                            <span class="badge bg-danger-subtle text-danger">
                                                -{{ $product->price->discount_percent }}%
                                            </span>
                                        @endif
                                    @else
                                        <span class="fw-bold text-dark">
                                            {{ number_format($product->price->regular_price, 2) }} ₾
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <i class="ci-chevron-right text-muted"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($this->results->count() >= 10)
                    <div>
                        <a href="{{ route('web.products.index', ['search' => $query]) }}"
                           class="d-block text-center py-3 text-decoration-none fw-semibold font-neue"
                           wire:click="closeSearch">
                            ყველა შედეგის ნახვა
                            <i class="ci-arrow-right ms-1"></i>
                        </a>
                    </div>
                @endif
            @else
                <!-- No Results -->
                <div class="p-4 text-center">
                    <i class="ci-search fs-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted mb-2">
                        ვერაფერი მოიძებნა "<strong>{{ $query }}</strong>"-ზე
                    </p>
                    <a href="{{ route('web.products.index') }}"
                       class="btn btn-sm btn-outline-primary"
                       wire:click="closeSearch">
                        ყველა პროდუქტის ნახვა
                    </a>
                </div>
            @endif
        </div>
    @endif
    <style>
        .search-item:hover {
            background-color: var(--bs-light) !important;
        }

        .search-item-container::-webkit-scrollbar {
            width: 6px;
        }

        .search-item-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .search-item-container::-webkit-scrollbar-thumb {
            background-color: #b5b5b5;
            border-radius: 10px;
        }

        .search-item-container::-webkit-scrollbar-thumb:hover {
            background-color: #8c8c8c;
        }
    </style>
</div>