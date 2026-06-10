<div>
    <div class="app-content content">
        <div class="content-wrapper">
            <div class="content-body">

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">"{{ $section->title }}" — პროდუქტების ჩაყრა</h4>
                        <a href="{{ route('dashboard.sections.index') }}" class="btn btn-outline-secondary">
                            <i data-feather="arrow-left"></i> უკან
                        </a>
                    </div>

                    <div class="card-body">

                        {{-- ფილტრები --}}
                        <div class="row mb-2">
                            <div class="col-md-4 mb-1">
                                <label class="form-label">კატეგორია</label>
                                <select class="form-select" wire:model.live="mainCategoryId">
                                    <option value="">— აირჩიე —</option>
                                    @foreach($mainCategories as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ $cat->translations->where('locale','ka')->first()?->title ?? ('#' . $cat->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label class="form-label">ქვეკატეგორია</label>
                                <select class="form-select" wire:model.live="subCategoryId" @disabled(!$mainCategoryId)>
                                    <option value="">— ყველა —</option>
                                    @foreach($subCategories as $sub)
                                        <option value="{{ $sub->id }}">
                                            {{ $sub->translations->where('locale','ka')->first()?->title ?? ('#' . $sub->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-1">
                                <label class="form-label">ძებნა (დასახელება/SKU)</label>
                                <input type="text" class="form-control" wire:model.live.debounce.400ms="search"
                                       placeholder="ძებნა...">
                            </div>
                        </div>

                        {{-- შედეგი + bulk --}}
                        @if($results && $results->total() > 0)
                            <div class="d-flex align-items-center justify-content-between mb-1 p-1 bg-light rounded">
                                <div class="form-check mb-0">
                                    <input type="checkbox" class="form-check-input" id="selectAll" wire:model.live="selectAll">
                                    <label class="form-check-label" for="selectAll">ამ გვერდის მონიშვნა</label>
                                </div>
                                <button class="btn btn-sm btn-success" wire:click="addSelected"
                                        @disabled(count($selected) === 0)>
                                    <i data-feather="plus-square"></i> მონიშნულის დამატება ({{ count($selected) }})
                                </button>
                            </div>

                            <div class="list-group mb-2">
                                @foreach($results as $p)
                                    <label class="list-group-item d-flex align-items-center justify-content-between" style="cursor:pointer">
                                        <div class="d-flex align-items-center">
                                            <input type="checkbox" class="form-check-input me-2"
                                                   value="{{ $p->id }}" wire:model.live="selected">
                                            <img src="{{ $p->main_image ? asset('storage/' . $p->main_image) : asset('web-assets/img/no-product.png') }}"
                                                 height="36" width="36" style="object-fit:contain" class="me-2" alt="">
                                            <div>
                                                <span class="badge badge-light-info">{{ $p->sku }}</span>
                                                {{ $p->translations->where('locale','ka')->first()?->title ?? '—' }}
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-success" wire:click.prevent="addProduct({{ $p->id }})">
                                            <i data-feather="plus"></i>
                                        </button>
                                    </label>
                                @endforeach
                            </div>

                            {{-- პაგინაცია --}}
                            {{ $results->links() }}

                        @elseif($results)
                            <div class="alert alert-warning"><div class="alert-body">ვერ მოიძებნა.</div></div>
                        @else
                            <div class="alert alert-secondary"><div class="alert-body">აირჩიე კატეგორია ან მოძებნე პროდუქტი.</div></div>
                        @endif

                        <hr>

                        {{-- სექციის პროდუქტები --}}
                        <h5 class="mb-2">სექციის პროდუქტები ({{ $section->products->count() }})</h5>
                        @if($section->products->count() > 0)
                            <div class="list-group">
                                @foreach($section->products as $p)
                                    <div class="list-group-item d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $p->main_image ? asset('storage/' . $p->main_image) : asset('web-assets/img/no-product.png') }}"
                                                 height="36" width="36" style="object-fit:contain" class="me-2" alt="">
                                            <div>
                                                <span class="badge badge-light-info">{{ $p->sku }}</span>
                                                {{ $p->translations->where('locale','ka')->first()?->title ?? '—' }}
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="removeProduct({{ $p->id }})">
                                            <i data-feather="trash-2"></i> მოშორება
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info"><div class="alert-body">ჯერ პროდუქტი არ არის დამატებული.</div></div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>