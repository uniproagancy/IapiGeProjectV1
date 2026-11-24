<div>
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">

                <form wire:submit.prevent="save">
                    <div class="row">

                        {{-- ===== მომხმარებელი ===== --}}
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">მომხმარებელი</h4>
                                </div>
                                <div class="card-body">
                                    <div class="mb-1">
                                        <label class="form-label">სახელი / კომპანია *</label>
                                        <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                               wire:model="customer_name" placeholder="მაგ. შპს „მაგალითი“ ან გიორგი გიორგაძე">
                                        @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">პირადი / საიდენტიფიკაციო კოდი</label>
                                        <input type="text" class="form-control" wire:model="customer_code">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">ტელეფონი</label>
                                        <input type="text" class="form-control" wire:model="customer_phone">
                                    </div>
                                    <div class="mb-1">
                                        <label class="form-label">ელ-ფოსტა</label>
                                        <input type="email" class="form-control @error('customer_email') is-invalid @enderror"
                                               wire:model="customer_email">
                                        @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">მისამართი</label>
                                        <input type="text" class="form-control" wire:model="customer_address">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ===== პოზიციები ===== --}}
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="card-title mb-0">პოზიციები</h4>
                                    <a href="{{ route('dashboard.invoice.index') }}" class="btn btn-sm btn-outline-secondary">
                                        ← ინვოისების სია
                                    </a>
                                </div>
                                <div class="card-body">

                                    {{-- კატალოგიდან დამატება --}}
                                    <div class="position-relative mb-2">
                                        <input type="text" class="form-control"
                                               wire:model.live.debounce.300ms="productSearch"
                                               placeholder="კატალოგიდან დამატება — დასახელება, SKU ან ID...">
                                        @if($this->searchResults->isNotEmpty())
                                            <div class="list-group position-absolute w-100 shadow" style="z-index: 20; max-height: 320px; overflow-y: auto;">
                                                @foreach($this->searchResults as $p)
                                                    @php $pp = (float) ($p->price?->discount_price ?: $p->price?->regular_price ?: 0); @endphp
                                                    <button type="button"
                                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                            wire:click="addProduct({{ $p->id }})">
                                                        <span>
                                                            {{ $p->translation('ka')?->title ?? ('#' . $p->id) }}
                                                            <small class="text-muted ms-50">{{ $p->sku }}</small>
                                                            @unless($p->active)
                                                                <span class="badge bg-light-secondary ms-50">გათიშული</span>
                                                            @endunless
                                                        </span>
                                                        <span class="fw-bold text-nowrap">{{ number_format($pp, 2) }} ₾</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table align-middle mb-1">
                                            <thead>
                                            <tr>
                                                <th>დასახელება</th>
                                                <th style="width: 110px;">რაოდენობა</th>
                                                <th style="width: 140px;">ფასი (₾)</th>
                                                <th style="width: 120px;" class="text-end">ჯამი</th>
                                                <th style="width: 44px;"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($items as $i => $item)
                                                @php
                                                    $line = (float) str_replace(',', '.', (string) $item['quantity'])
                                                          * (float) str_replace(',', '.', (string) $item['price']);
                                                @endphp
                                                <tr wire:key="item-{{ $i }}">
                                                    <td>
                                                        <input type="text"
                                                               class="form-control form-control-sm @error('items.'.$i.'.name') is-invalid @enderror"
                                                               wire:model="items.{{ $i }}.name"
                                                               placeholder="პროდუქტი ან მომსახურება">
                                                        @if($item['product_id'])
                                                            <small class="text-muted">კატალოგიდან · ID {{ $item['product_id'] }}</small>
                                                        @endif
                                                        @error('items.'.$i.'.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </td>
                                                    <td>
                                                        <input type="text" inputmode="decimal"
                                                               class="form-control form-control-sm @error('items.'.$i.'.quantity') is-invalid @enderror"
                                                               wire:model.blur="items.{{ $i }}.quantity">
                                                    </td>
                                                    <td>
                                                        <input type="text" inputmode="decimal"
                                                               class="form-control form-control-sm @error('items.'.$i.'.price') is-invalid @enderror"
                                                               wire:model.blur="items.{{ $i }}.price"
                                                               placeholder="0.00">
                                                        @error('items.'.$i.'.price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                    </td>
                                                    <td class="text-end text-nowrap">{{ number_format($line, 2) }} ₾</td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-icon btn-flat-danger"
                                                                wire:click="removeItem({{ $i }})" title="წაშლა">✕</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addItem">
                                        + პოზიციის დამატება ხელით
                                    </button>

                                    <hr>

                                    <div class="row">
                                        <div class="col-md-7 mb-1">
                                            <label class="form-label">შენიშვნა ინვოისზე</label>
                                            <textarea class="form-control" rows="3" wire:model="note"
                                                      placeholder="მაგ. გადახდის ვადა, დანიშნულება..."></textarea>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="mb-1">
                                                <label class="form-label">მიტანის მომსახურება (₾)</label>
                                                <input type="text" inputmode="decimal"
                                                       class="form-control @error('delivery_amount') is-invalid @enderror"
                                                       wire:model.blur="delivery_amount">
                                                @error('delivery_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">პროდუქციის ჯამი</span>
                                                <span>{{ number_format($this->subtotal, 2) }} ₾</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">მათ შორის დღგ (18%)</span>
                                                <span>{{ number_format($this->total * 0.18 / 1.18, 2) }} ₾</span>
                                            </div>
                                            <div class="d-flex justify-content-between fw-bolder fs-5 mt-50">
                                                <span>სრული თანხა</span>
                                                <span>{{ number_format($this->total, 2) }} ₾</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end mt-2">
                                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                            <span wire:loading.remove wire:target="save">ინვოისის შექმნა და ჩამოტვირთვა</span>
                                            <span wire:loading wire:target="save">
                                                <span class="spinner-border spinner-border-sm me-50"></span>იქმნება...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
