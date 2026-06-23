<div>
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">პროდუქციის ჩამონათვალი ({{$products->total()}})</h4>
                        <div>
                            {{-- Bulk Actions --}}
                            @if(!empty($selectedProducts))
                                <div class="btn-group">
                                    <button class="btn btn-icon btn-warning dropdown-toggle px-1" type="button"
                                            id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i data-feather="list"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeCategoryModal">კატეგორიის ცვლილება</a>
                                        <a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changeBrandModal">ბრენდის ცვლილება</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-warning" href="#"
                                           onclick="if(confirm('{{ count($selectedProducts) }} პროდუქტი ჩაიკეტება.')) { @this.call('bulkLock') }; return false;">
                                            <i data-feather="lock" class="me-1" style="width:14px;"></i>
                                            ჩაკეტვა ({{ count($selectedProducts) }})
                                        </a>
                                        <a class="dropdown-item text-success" href="#"
                                           onclick="if(confirm('{{ count($selectedProducts) }} პროდუქტი განიბლოკება.')) { @this.call('bulkUnlock') }; return false;">
                                            <i data-feather="unlock" class="me-1" style="width:14px;"></i>
                                            განბლოკვა ({{ count($selectedProducts) }})
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#"
                                           onclick="if(confirm('{{ count($selectedProducts) }} პროდუქტი წაიშლება?')) { @this.call('bulkDelete') }; return false;">
                                            <i data-feather="trash-2" class="me-1" style="width:14px;"></i>
                                            წაშლა ({{ count($selectedProducts) }})
                                        </a>
                                        @if($with_trashed)
                                            <a class="dropdown-item text-success" href="#"
                                               onclick="if(confirm('{{ count($selectedProducts) }} პროდუქტი აღდგება?')) { @this.call('bulkRestore') }; return false;">
                                                <i data-feather="rotate-ccw" class="me-1" style="width:14px;"></i>
                                                აღდგენა ({{ count($selectedProducts) }})
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Export --}}
                            <a class="btn btn-sm btn-outline-danger" href="{{ route('dashboard.global.export') }}" target="_blank">
                                <i data-feather="download" class="me-1" style="width:14px;"></i>
                                ვერ ნაპოვნები ({{ \App\Models\Product\MetroMartNotFound::count() }})
                            </a>

                            {{-- Upload Dropdown --}}
                            <div class="btn-group">
                                <button class="btn btn-info dropdown-toggle" type="button"
                                        id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                    განახლების ატვირთვა
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#uploadMideaModal">
                                        <i data-feather="upload" class="me-1" style="width:14px;"></i> Midea
                                    </a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#uploadKontaktModal">
                                        <i data-feather="upload" class="me-1" style="width:14px;"></i> KontaktHome
                                    </a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#uploadComfoModal">
                                        <i data-feather="upload" class="me-1" style="width:14px;"></i> Comfo
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#uploadEliteModal">
                                        <i data-feather="upload" class="me-1" style="width:14px;"></i> Elite — Excel
                                    </a>
                                    <a class="dropdown-item" href="{{ route('elite.scan') }}"
                                       onclick="return confirm('Elite სკანი დაიწყება?')">
                                        <i data-feather="search" class="me-1" style="width:14px;"></i> Elite — სკანი
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#uploadMetromartModal">
                                        <i data-feather="upload" class="me-1" style="width:14px;"></i> Metromart — Excel
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#uploadAlneoModal">
                                        <i data-feather="upload" class="me-1" style="width:14px;"></i> Alneo — Excel
                                    </a>
                                    <a class="dropdown-item text-danger" href="#"
                                       wire:click="uploadAlneoScan"
                                       onclick="if(!confirm('Alneo სკანი დაიწყებს ყველა პროდუქტის იმპორტს')) return false;">
                                        <i data-feather="radio" class="me-1" style="width:14px;"></i> Alneo — სკანი
                                    </a>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <a href="{{ route('dashboard.product.create') }}" class="btn btn-icon btn-success mx-50">
                                <i data-feather="plus-square"></i>
                            </a>
                            <button type="button" class="btn btn-icon btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#filterProductModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>

                    @if(count($products) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr class="text-center">
                                    <th><input type="checkbox" wire:model.live="selectAll" id="select-all"></th>
                                    <th>სურათი</th>
                                    <th class="text-start">დასახელება</th>
                                    <th>ფასი</th>
                                    <th>კატეგორია</th>
                                    <th>ბრენდი</th>
                                    <th>სტატუსი</th>
                                    <th>საიტზე ჩვენება</th>
                                    <th>ჩაკეტვა</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($products as $product)
                                    <tr class="text-center">
                                        <td>
                                            <input type="checkbox" wire:model.live="selectedProducts" value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            <div class="avatar-group">
                                                <div data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                     data-bs-placement="top" class="avatar pull-up my-0">
                                                    <img src="{{ asset('storage/' . $product->main_image) }}" alt=""
                                                         height="40" width="40"/>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge badge-light-info">{{ $product->sku }}</span> -
                                            {{ $product->translations->where('locale', 'ka')->first()?->title ?? ' ' }}
                                        </td>
                                        <td>
                                            @if(!empty($product->price->discount_price))
                                                <span class="badge badge-light-success">{{ $product->price->discount_price }} ₾</span>
                                                <br><br>
                                                <span class="badge badge-light-danger">{{ $product->price->regular_price }} ₾</span>
                                            @else
                                                <span class="badge badge-light-success">{{ $product->price->regular_price ?? '' }} ₾</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($product->category?->parent?->translations))
                                                {{ $product->category->parent->translations->where('locale', 'ka')->first()?->title ?? '' }}
                                            @endif
                                            @if(!empty($product->category?->translations))
                                                / {{ $product->category->translations->where('locale', 'ka')->first()?->title ?? '' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($product->brand?->translations))
                                                {{ $product->brand->translations->where('locale', 'ka')->first()?->title ?? '' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$product->trashed())
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input"
                                                               id="product_active_{{ $product->id }}"
                                                               wire:click="toggleActive({{ $product->id }})" @checked($product->active) />
                                                        <label class="form-check-label" for="product_active_{{ $product->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$product->trashed() && $product->active != 0)
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input"
                                                               id="product_show_{{ $product->id }}"
                                                               wire:click="toggleShow({{ $product->id }})" @checked($product->show) />
                                                        <label class="form-check-label" for="product_show_{{ $product->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$product->trashed())
                                                <a href="#" class="text-body"
                                                   wire:click="toggleLock({{ $product->id }})"
                                                   title="{{ $product->update_lock ? 'განბლოკვა' : 'ჩაკეტვა' }}">
                                                    @if($product->update_lock)
                                                        <i class="text-warning" data-feather="lock"></i>
                                                    @else
                                                        <i class="text-muted" data-feather="unlock"></i>
                                                    @endif
                                                </a>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->id !== 1)
                                                @if($product->trashed())
                                                    <a href="#" class="text-body" wire:click="restoreModal({{ $product->id }})">
                                                        <i class="text-success" data-feather="rotate-ccw"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('dashboard.product.update', $product->id) }}" class="text-body">
                                                        <i data-feather="edit"></i>
                                                    </a>
                                                    <a href="#" class="text-body" wire:click="priceEditModal({{ $product->id }})">
                                                        <i class="text-success" data-feather="dollar-sign"></i>
                                                    </a>
                                                    <a href="#" class="text-body" wire:click="deleteModal({{ $product->id }})">
                                                        <i class="text-danger" data-feather="trash"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-2">
                            <div class="alert alert-warning" role="alert">
                                <div class="alert-body d-flex align-items-center">
                                    <i data-feather="alert-circle" class="me-50"></i>
                                    <span>ჩამონათვალი ცარიელია!</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{ $products->links() }}
    </div>

    {{-- ==================== MODALS ==================== --}}

    {{-- Midea --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadMideaModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadMidea">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">Midea — ატვირთვა</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">ფაილი (.xlsx / .csv) — A=მოდელი | B=Stock</label>
                        <input type="file"
                               class="form-control @error('midea_file') border-danger is-invalid @enderror"
                               wire:model="midea_file" accept=".xlsx,.xls,.csv">
                        @error('midea_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled" wire:target="uploadMidea,midea_file">
                            <span wire:loading.remove wire:target="uploadMidea">დამუშავება</span>
                            <span wire:loading wire:target="uploadMidea"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- KontaktHome --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadKontaktModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadKontakt">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">KontaktHome — ატვირთვა</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">ფაილი (.xlsx) — დასახელება (ლინკით) | Stock | Price</label>
                        <input type="file"
                               class="form-control @error('kontakt_file') border-danger is-invalid @enderror"
                               wire:model="kontakt_file" accept=".xlsx,.xls">
                        @error('kontakt_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled" wire:target="uploadKontakt,kontakt_file">
                            <span wire:loading.remove wire:target="uploadKontakt">დამუშავება</span>
                            <span wire:loading wire:target="uploadKontakt"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Comfo --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadComfoModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadComfo">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">Comfo — ატვირთვა</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">ფაილი (.xlsx) — A=ID | B=რაოდენობა | C=Product Link</label>
                        <input type="file"
                               class="form-control @error('comfoFile') border-danger is-invalid @enderror"
                               wire:model="comfoFile" accept=".xlsx,.xls">
                        @error('comfoFile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled" wire:target="uploadComfo,comfoFile">
                            <span wire:loading.remove wire:target="uploadComfo">დამუშავება</span>
                            <span wire:loading wire:target="uploadComfo"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Elite --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadEliteModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadElite">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">Elite Electronics — BarCode-ების ატვირთვა</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">ფაილი (.xlsx) — A სვეტი = BarCode</label>
                        <input type="file"
                               class="form-control @error('elite_file') border-danger is-invalid @enderror"
                               wire:model="elite_file" accept=".xlsx,.xls">
                        @error('elite_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled" wire:target="uploadElite,elite_file">
                            <span wire:loading.remove wire:target="uploadElite">დამუშავება</span>
                            <span wire:loading wire:target="uploadElite"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Metromart --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadMetromartModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadMetromart">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">Metromart — მოდელების ატვირთვა</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">ფაილი (.xlsx) — A სვეტი = მოდელი</label>
                        <input type="file"
                               class="form-control @error('metromart_file') border-danger is-invalid @enderror"
                               wire:model="metromart_file" accept=".xlsx,.xls">
                        @error('metromart_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled" wire:target="uploadMetromart,metromart_file">
                            <span wire:loading.remove wire:target="uploadMetromart">დამუშავება</span>
                            <span wire:loading wire:target="uploadMetromart"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Alneo Excel --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadAlneoModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadAlneoExcel">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">Alneo — Excel განახლება</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">ფაილი (.xlsx) — A=SKU | B=Stock | C=Price | D=Discount Price</label>
                        <input type="file"
                               class="form-control @error('alneo_file') border-danger is-invalid @enderror"
                               wire:model="alneo_file" accept=".xlsx,.xls">
                        @error('alneo_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="alert alert-info py-1 px-2 mt-1" style="font-size:12px;">
                        SKU = ალნეოს კოდი (ALNEO- პრეფიქსის გარეშე). პროდუქტი ბაზაში უნდა არსებობდეს.
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled" wire:target="uploadAlneoExcel,alneo_file">
                            <span wire:loading.remove wire:target="uploadAlneoExcel">დამუშავება</span>
                            <span wire:loading wire:target="uploadAlneoExcel"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filter --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterProductModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="applyFilters" wire:keydown.enter="applyFilters">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">ფილტრი</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">საძიებო სიტყვა</label>
                        <input type="text" class="form-control" placeholder="დასახელება, SKU" wire:model.lazy="search_query"/>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">სორტირება</label>
                        <select class="form-select" wire:model.lazy="order_dir">
                            <option value="desc">ახალ დამატებული</option>
                            <option value="asc">ძველ დამატებული</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">კატეგორია</label>
                        <select class="form-select" wire:model.lazy="category_id">
                            <option value="">ყველა</option>
                            @foreach($categories->where('parent_id', 0)->where('active', 1) as $category)
                                <option value="{{ $category->id }}">{{ $category->translations->where('locale','ka')->first()?->title ?? ('#' . $category->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ჩვენება გვერდზე</label>
                        <select class="form-select" wire:model.lazy="per_page">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ბრენდი</label>
                        <select class="form-select" wire:model.lazy="brand_id">
                            <option value="">ყველა</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->translations->where('locale','ka')->first()?->title ?? ('ბრენდი #' . $brand->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">მომწოდებელი</label>
                        <select class="form-select" wire:model.lazy="supplier_id">
                            <option value="">ყველა</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->translations->where('locale','ka')->first()?->name ?? ('#' . $supplier->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ნაშთი</label>
                        <select class="form-select" wire:model.lazy="no_stock">
                            <option value="">ყველა</option>
                            <option value="0">მხოლოდ ნულოვანი ნაშთი</option>
                            <option value="1">არსებული ნაშთიანი</option>
                        </select>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="status_active" wire:model.lazy="status_active">
                        <label class="form-check-label" for="status_active">მხოლოდ აქტიურები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="show_web" wire:model.lazy="show_web">
                        <label class="form-check-label" for="show_web">მხოლოდ საიტზე ნაჩვენები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="with_trashed" wire:model.lazy="with_trashed">
                        <label class="form-check-label" for="with_trashed">წაშლილი ჩანაწერები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="unsorted" wire:model.lazy="unsorted">
                        <label class="form-check-label" for="unsorted">დაუხარისხებელი</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="no_brand" wire:model.lazy="no_brand">
                        <label class="form-check-label" for="no_brand">ბრენდის გარეშე</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="only_locked" wire:model.lazy="only_locked">
                        <label class="form-check-label" for="only_locked">მხოლოდ ჩაკეტილები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="draft" wire:model.lazy="draft">
                        <label class="form-check-label" for="draft">მხოლოდ Draft</label>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1">გაფილტრე</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">გასუფთავება</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Change Category --}}
    <div class="modal fade" wire:ignore.self id="changeCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">კატეგორიის ცვლილება</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">კატეგორია</label>
                        <select class="form-select" wire:model.live="selectedCategory">
                            <option value="">— აირჩიეთ —</option>
                            @foreach($categories->where('parent_id', 0)->where('active', 1) as $category)
                                <option value="{{ $category->id }}">{{ $category->translations->where('locale', 'ka')->first()?->title ?? ('#' . $category->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!empty($subcategories))
                        <div class="mb-2">
                            <label class="form-label">ქვეკატეგორია</label>
                            <select class="form-select" wire:model="selectedSubcategory">
                                <option value="">— აირჩიეთ —</option>
                                @foreach($subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}">{{ $subcategory->translations->where('locale', 'ka')->first()?->title ?? ('#' . $subcategory->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="updateProductCategory">დადასტურება</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Change Brand --}}
    <div class="modal fade" wire:ignore.self id="changeBrandModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ბრენდის ცვლილება</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <select class="form-select" wire:model.live="selectedBrand">
                            <option value="">— აირჩიეთ —</option>
                            @foreach($brands->where('active', 1) as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->translations->where('locale', 'ka')->first()?->title ?? ('ბრენდი #' . $brand->id) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="updateProductBrand">დადასტურება</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Price Edit --}}
    <div class="modal fade" wire:ignore.self id="priceEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ფასის რედაქტირება</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">ფასი <span class="text-danger">*</span></label>
                        <input type="number" step="0.01"
                               class="form-control @error('priceEditRegularPrice') border-danger is-invalid @enderror"
                               wire:model="priceEditRegularPrice" placeholder="0.00">
                        @error('priceEditRegularPrice')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ფასდაკლების ფასი</label>
                        <input type="number" step="0.01" class="form-control"
                               wire:model="priceEditDiscountPrice" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="updatePrice">შენახვა</button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>

</div>

@section('page_scripts')
    <script>
        Livewire.on('swal:deleteModal', data => {
            Swal.fire({
                title: data[0].title,
                icon: data[0].icon,
                showCancelButton: true,
                confirmButtonText: data[0].confirmButtonText,
                cancelButtonText: data[0].cancelButtonText,
            }).then((result) => {
                if (result.isConfirmed) Livewire.dispatch('delete', {id: data[0].id});
            });
        });

        Livewire.on('swal:restoreModal', data => {
            Swal.fire({
                title: data[0].title,
                icon: data[0].icon,
                showCancelButton: true,
                confirmButtonText: data[0].confirmButtonText,
                cancelButtonText: data[0].cancelButtonText,
            }).then((result) => {
                if (result.isConfirmed) Livewire.dispatch('restore', {id: data[0].id});
            });
        });

        Livewire.on('filter_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('filterProductModal'))?.hide();
        });

        Livewire.on('uploadComfoModal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('uploadComfoModal'))?.hide();
        });

        Livewire.on('uploadEliteModal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('uploadEliteModal'))?.hide();
        });

        Livewire.on('uploadAlneoModal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('uploadAlneoModal'))?.hide();
        });

        Livewire.on('category_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('changeCategoryModal'))?.hide();
        });

        Livewire.on('brand_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('changeBrandModal'))?.hide();
        });

        Livewire.on('bulk_modal_close', () => {
            ['changeCategoryModal', 'changeBrandModal'].forEach(id => {
                bootstrap.Modal.getInstance(document.getElementById(id))?.hide();
            });
        });

        Livewire.on('price_edit_modal_open', () => {
            new bootstrap.Modal(document.getElementById('priceEditModal')).show();
        });

        Livewire.on('price_edit_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('priceEditModal'))?.hide();
        });
    </script>
@endsection