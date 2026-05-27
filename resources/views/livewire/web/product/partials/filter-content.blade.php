{{-- ფასის ფილტრი --}}
<div class="filter-block">
    <div class="filter-section-title {{ ($priceMin || $priceMax) ? 'has-selected' : '' }}"
         data-bs-toggle="collapse"
         data-bs-target="#filter_price">
        <span>
            ფასი
            @if($priceMin || $priceMax)
                <span class="filter-selected-badge">✓</span>
            @endif
        </span>
        <i class="ci-chevron-down fs-sm"></i>
    </div>
    <div class="collapse show" id="filter_price">
        <div class="filter-body">
            <div class="d-flex gap-2 mb-2">
                <input type="number"
                       class="form-control form-control-sm"
                       wire:model.live.debounce.500ms="priceMin"
                       placeholder="მინ. ₾"
                       min="0">
                <input type="number"
                       class="form-control form-control-sm"
                       wire:model.live.debounce.500ms="priceMax"
                       placeholder="მაქს. ₾"
                       min="0">
            </div>
            @if($priceMin || $priceMax)
                <button wire:click="clearPriceFilter"
                        class="btn btn-sm btn-outline-secondary w-100 mt-1"
                        style="font-size: 12px;">
                    გასუფთავება
                </button>
            @endif
        </div>
    </div>
</div>

{{-- კატეგორიები --}}
<div class="filter-block">
    <div class="filter-section-title"
         data-bs-toggle="collapse"
         data-bs-target="#filter_categories">
        <span>{{ $currentCategory?->translation('ka')->title ?? 'კატეგორიები' }}</span>
        <i class="ci-chevron-down fs-sm"></i>
    </div>
    <div class="collapse show" id="filter_categories">
        <div class="filter-body">
            @if(!$selectedParent)
                <ul class="list-unstyled m-0">
                    @foreach($parentCategories as $category)
                        <li class="py-1">
                            <a class="text-body text-decoration-none"
                               style="font-size: 13px;"
                               href="#"
                               wire:click.prevent="selectParent({{ $category->id }})">
                                {{ $category->translation('ka')->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <ul class="list-unstyled m-0">
                    @foreach($subCategories as $subCategory)
                        <li class="py-1">
                            <a class="text-decoration-none {{ $currentCategory?->id === $subCategory->id ? 'text-primary fw-semibold' : 'text-body' }}"
                               style="font-size: 13px;"
                               href="#"
                               wire:click.prevent="selectChild({{ $subCategory->id }})">
                                {{ $subCategory->translation('ka')->title }}
                            </a>
                        </li>
                    @endforeach
                    <li class="pt-2">
                        <a href="#"
                           wire:click.prevent="resetCategories"
                           class="text-primary"
                           style="font-size: 12px;">
                            ← უკან
                        </a>
                    </li>
                </ul>
            @endif
        </div>
    </div>
</div>

{{-- სპეციფიკაციების ფილტრი --}}
@if(!empty($specificationSections) && $specificationSections->count() > 0)
    @foreach($specificationSections as $specName => $values)
        @php
            $selectedInSection = collect($selectedSpecs)->filter(
                fn($s) => str_starts_with($s, $specName . '::')
            );
            $hasSelected = $selectedInSection->count() > 0;
            $specKey     = 'spec_' . md5($specName);
            $valuesCount = count($values);
        @endphp
        <div class="filter-block">
            <div class="filter-section-title {{ $hasSelected ? 'has-selected' : '' }}"
                 data-bs-toggle="collapse"
                 data-bs-target="#{{ $specKey }}">
                <span>
                    {{ $specName }}
                    @if($hasSelected)
                        <span class="filter-selected-badge">{{ $selectedInSection->count() }}</span>
                    @endif
                </span>
                <i class="ci-chevron-down fs-sm"></i>
            </div>
            <div class="collapse show" id="{{ $specKey }}">
                <div class="filter-body">
                    {{-- პირველი 5 --}}
                    @foreach($values as $i => $item)
                        @if($i < 5)
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       wire:model.live="selectedSpecs"
                                       value="{{ $specName }}::{{ $item->value }}"
                                       id="spec_{{ md5($specName . $item->value) }}">
                                <label class="form-check-label"
                                       for="spec_{{ md5($specName . $item->value) }}">
                                    {{ $item->value }}
                                </label>
                            </div>
                        @endif
                    @endforeach

                    {{-- 5-ზე მეტი → Alpine collapse --}}
                    @if($valuesCount > 5)
                        <div x-data="{ open: {{ $hasSelected ? 'true' : 'false' }} }">
                            @foreach($values as $i => $item)
                                @if($i >= 5)
                                    @php $isSelected = $selectedInSection->contains($specName . '::' . $item->value); @endphp
                                    <div class="form-check"
                                         x-show="open"
                                         @if(!$isSelected) style="display:none;" @endif>
                                        <input type="checkbox"
                                               class="form-check-input"
                                               wire:model.live="selectedSpecs"
                                               value="{{ $specName }}::{{ $item->value }}"
                                               id="specm_{{ md5($specName . $item->value) }}">
                                        <label class="form-check-label"
                                               for="specm_{{ md5($specName . $item->value) }}">
                                            {{ $item->value }}
                                        </label>
                                    </div>
                                @endif
                            @endforeach
                            <button class="filter-show-more mt-2"
                                    type="button"
                                    @click="open = !open">
                                <span x-show="!open">მეტის ნახვა ({{ $valuesCount - 5 }}) ↓</span>
                                <span x-show="open">ნაკლები ↑</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endif

{{-- მხოლოდ ფასდაკლებული --}}
<div class="filter-block pt-2">
    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               wire:model.live="onlyDiscounted"
               id="discountFilter">
        <label class="form-check-label fw-medium"
               style="font-size: 13px;"
               for="discountFilter">
            მხოლოდ ფასდაკლებული
        </label>
    </div>
</div>