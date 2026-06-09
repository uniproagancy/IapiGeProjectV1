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
                        {{-- ძებნა --}}
                        <div class="mb-2">
                            <label class="form-label">პროდუქტის ძებნა (დასახელება ან SKU)</label>
                            <input type="text" class="form-control" wire:model.live.debounce.400ms="search"
                                   placeholder="ჩაწერეთ მინ. 2 სიმბოლო...">
                        </div>

                        {{-- ძებნის შედეგი --}}
                        @if(count($results) > 0)
                            <div class="list-group mb-3">
                                @foreach($results as $p)
                                    <div class="list-group-item d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $p->main_image ? asset('storage/' . $p->main_image) : asset('web-assets/img/no-product.png') }}"
                                                 height="36" width="36" style="object-fit:contain" class="me-2" alt="">
                                            <div>
                                                <span class="badge badge-light-info">{{ $p->sku }}</span>
                                                {{ $p->translations->where('locale','ka')->first()?->title ?? '—' }}
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-success" wire:click="addProduct({{ $p->id }})">
                                            <i data-feather="plus"></i> დამატება
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(mb_strlen(trim($search)) >= 2)
                            <div class="alert alert-warning"><div class="alert-body">ვერ მოიძებნა.</div></div>
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