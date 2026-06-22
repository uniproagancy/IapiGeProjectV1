<div class="position-relative live-search-wrap"
     x-data="{
         open: @entangle('isOpen'),
         handleKeydown(e) {
             if (!this.open) return;
             if (e.key === 'ArrowDown')      { e.preventDefault(); $wire.navigateDown(); }
             else if (e.key === 'ArrowUp')   { e.preventDefault(); $wire.navigateUp(); }
             else if (e.key === 'Enter')     { e.preventDefault(); $wire.selectCurrent(); }
             else if (e.key === 'Escape')    { $wire.closeSearch(); }
         }
     }"
     @click.outside="$wire.closeSearch()"
     @keydown.window="handleKeydown($event)">
    {{-- Search Input --}}
    <div class="position-relative live-search-input-wrap">
        <i class="ci-search position-absolute top-50 start-0 translate-middle-y d-flex fs-base text-muted ms-3"
           style="z-index: 5; pointer-events: none;"></i>
        <input type="search"
               class="form-control form-control-lg live-search-input"
               placeholder="{{ trans('trans.search_your_product') }}"
               wire:model.live.debounce.300ms="query"
               @focus="$wire.set('isOpen', true)"
               autocomplete="off">
        {{-- Loading spinner --}}
        <div wire:loading wire:target="query"
             class="position-absolute top-50 end-0 translate-middle-y me-5">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        </div>
        @if($query)
            <button type="button"
                    class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted p-0 me-3 live-search-clear"
                    wire:click="$set('query', '')"
                    wire:loading.remove wire:target="query">
                <i class="ci-close fs-base"></i>
            </button>
        @endif
    </div>
    {{-- Results Dropdown --}}
    @if($isOpen && mb_strlen($query) >= 2)
        <div class="live-search-dropdown"
             x-show="open"
             x-transition.opacity.duration.150ms>
            @if($this->results->count() > 0)
                <div class="live-search-header">
                    <span class="text-muted small">ნაპოვნია <strong class="text-dark">{{ $this->results->count() }}</strong> შედეგი</span>
                </div>
                <div class="live-search-list">
                    @foreach($this->results as $index => $product)
                        @php
                            $translation = $product->translation(app()->getLocale()) ?? $product->translation('ka');
                            $cat = $product->category;
                            $categoryTitle = ($cat && $cat->parent_id !== 2)
                                ? ($cat->translation(app()->getLocale())?->title ?? $cat->translation('ka')?->title)
                                : null;
                            $hasDiscount = !empty($product->price->discount_price) && $product->price->discount_price > 0;
                            $finalPrice  = $hasDiscount ? $product->price->discount_price : $product->price->regular_price;
                        @endphp
                        <a href="{{ route('web.products.view', $translation->slug) }}"
                           wire:navigate
                           class="live-search-item {{ $selectedIndex === $index ? 'is-active' : '' }}"
                           wire:key="search-result-{{ $product->id }}"
                           @mouseenter="$wire.selectedIndex = {{ $index }}"
                           wire:click="closeSearch">
                            <div class="live-search-item__img">
                                @if($product->main_image)
                                    <img src="{{ asset('storage/' . $product->main_image) }}"
                                         alt="{{ $translation->title }}"
                                         loading="lazy">
                                @else
                                    <div class="live-search-item__img-placeholder">
                                        <i class="ci-image"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="live-search-item__body">
                                @if($categoryTitle)
                                    <div class="live-search-item__category">{{ $categoryTitle }}</div>
                                @endif
                                <div class="live-search-item__title">{{ $translation->title }}</div>
                            </div>
                            <div class="live-search-item__price">
                                @if($hasDiscount)
                                    <div class="live-search-item__price-now">{{ number_format($finalPrice, 2) }} ₾</div>
                                    <div class="live-search-item__price-old">{{ number_format($product->price->regular_price, 2) }} ₾</div>
                                    @if($product->price->discount_percent)
                                        <span class="live-search-item__badge">-{{ $product->price->discount_percent }}%</span>
                                    @endif
                                @else
                                    <div class="live-search-item__price-now">{{ number_format($finalPrice, 2) }} ₾</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('web.products.index', ['search' => $query]) }}"
                   class="live-search-footer"
                   wire:navigate
                   wire:click="closeSearch">
                    ყველა შედეგის ნახვა "<strong>{{ $query }}</strong>"-ზე
                    <i class="ci-arrow-right ms-1"></i>
                </a>
            @else
                <div class="live-search-empty">
                    <div class="live-search-empty__icon">
                        <i class="ci-search"></i>
                    </div>
                    <div class="live-search-empty__title">ვერაფერი მოიძებნა</div>
                    <div class="live-search-empty__sub">სცადეთ სხვა საძიებო სიტყვა "<strong>{{ $query }}</strong>"-ის ნაცვლად</div>
                    <a href="{{ route('web.products.index') }}"
                       class="btn btn-sm btn-outline-primary mt-3"
                       wire:click="closeSearch">
                        ყველა პროდუქტი
                    </a>
                </div>
            @endif
        </div>
    @endif
    <style>
        /* === Selection === */
        .live-search-input::selection,
        .live-search-input::-moz-selection {
            background: #b3d4ff;
            color: #1a1a1a;
        }

        /* === Search Input === */
        .live-search-input {
            font-size: 14px;
            padding-left: 2.8rem;
            padding-right: 3rem;
            background: #fff;
            border: 1px solid transparent;
            border-radius: 12px !important;
            color: #1a1a1a;
            font-weight: 500;
            transition: all .2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .live-search-input::placeholder {
            color: #aaa;
            font-weight: 400;
        }
        .live-search-input:focus {
            box-shadow: 0 4px 12px rgba(255,105,0,0.15);
            border-color: #ff6900;
            background: #fff;
            color: #1a1a1a;
        }
        .live-search-clear {
            z-index: 5;
            transition: color .15s ease;
        }
        .live-search-clear:hover { color: #ff6900 !important; }
        /* === Dropdown === */
        .live-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.04);
            overflow: hidden;
            z-index: 1050;
            animation: searchSlideDown .2s ease;
        }
        @keyframes searchSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .live-search-header {
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }
        .live-search-list {
            max-height: 440px;
            overflow-y: auto;
        }
        .live-search-list::-webkit-scrollbar { width: 6px; }
        .live-search-list::-webkit-scrollbar-track { background: transparent; }
        .live-search-list::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
        .live-search-list::-webkit-scrollbar-thumb:hover { background: #ff6900; }
        .live-search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: #2a2a2a;
            border-bottom: 1px solid #f4f4f4;
            transition: background-color .15s ease;
            cursor: pointer;
        }
        .live-search-item:last-child { border-bottom: none; }
        .live-search-item:hover,
        .live-search-item.is-active {
            background: #fff7f0;
            color: #2a2a2a;
        }
        .live-search-item__img {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
            border: 1px solid #ececec;
            border-radius: 10px;
            overflow: hidden;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .live-search-item__img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: multiply;
        }
        .live-search-item__img-placeholder {
            color: #ccc;
            font-size: 24px;
        }
        .live-search-item__body {
            flex: 1;
            min-width: 0;
        }
        .live-search-item__category {
            font-size: 11px;
            color: #ff6900;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .3px;
            margin-bottom: 2px;
        }
        .live-search-item__title {
            font-size: 13.5px;
            font-weight: 500;
            color: #1a1a1a;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .live-search-item__price {
            flex-shrink: 0;
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }
        .live-search-item__price-now {
            font-size: 15px;
            font-weight: 700;
            color: #ff6900;
            white-space: nowrap;
        }
        .live-search-item__price-old {
            font-size: 11px;
            color: #999;
            text-decoration: line-through;
            white-space: nowrap;
        }
        .live-search-item__badge {
            display: inline-block;
            background: #ff3b3b;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 6px;
            margin-top: 2px;
        }
        .live-search-footer {
            display: block;
            text-align: center;
            padding: 14px 16px;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
            color: #1a1a1a;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all .15s ease;
        }
        .live-search-footer:hover {
            background: #ff6900;
            color: #fff;
        }
        .live-search-empty {
            padding: 32px 20px;
            text-align: center;
        }
        .live-search-empty__icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #fff7f0;
            color: #ff6900;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .live-search-empty__title {
            font-size: 15px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        .live-search-empty__sub {
            font-size: 13px;
            color: #888;
        }
        @media (max-width: 576px) {
            .live-search-item__img { width: 48px; height: 48px; }
            .live-search-item__title { font-size: 13px; }
            .live-search-item__price-now { font-size: 14px; }
            .live-search-item { padding: 10px 12px; }
        }
    </style>
</div>