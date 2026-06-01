@forelse($specificationSections as $name => $values)
    <div class="spec-filter-block border rounded p-3 mb-3" x-data="{ expanded: false }" wire:key="spec-{{ $loop->index }}">
        <h4 class="h6 fw-semibold mb-3">{{ $name }}</h4>

        <div class="d-flex flex-column gap-2">
            @foreach($values as $i => $item)
                <div class="form-check"
                     @if($i >= 5) x-show="expanded" x-cloak @endif>
                    <input class="form-check-input"
                           type="checkbox"
                           id="spec-{{ $loop->parent->index }}-{{ $i }}"
                           value="{{ $name }}::{{ $item->value }}"
                           wire:model.live="selectedSpecs">
                    <label class="form-check-label" for="spec-{{ $loop->parent->index }}-{{ $i }}">
                        {{ $item->value }}
                    </label>
                </div>
            @endforeach
        </div>

        @if($values->count() > 5)
            <button type="button" class="btn btn-link btn-sm p-0 mt-2 text-decoration-none"
                    @click="expanded = !expanded">
                <span x-show="!expanded">მეტის ნახვა ({{ $values->count() - 5 }})</span>
                <span x-show="expanded" x-cloak>ნაკლების ნახვა</span>
            </button>
        @endif
    </div>
@empty
@endforelse

@if(!empty($selectedSpecs) && count($selectedSpecs))
    <button wire:click="clearSpecFilter" class="btn btn-sm btn-outline-secondary w-100 mb-3">
        სპეციფიკაციების გასუფთავება
    </button>
@endif