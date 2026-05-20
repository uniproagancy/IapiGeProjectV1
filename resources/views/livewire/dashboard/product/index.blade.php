<div>
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">პროდუქციის ჩამონათვალი ({{ $products->total() }})</h4>
                        <div class="d-flex gap-1">
                            {{-- ✅ Bulk actions --}}
                            @if(!empty($selectedProducts))
                                <button class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#bulkActionsModal">
                                    მონიშნული ({{ count($selectedProducts) }})
                                </button>
                            @endif

                            <div class="btn-group">
                                <button class="btn btn-info dropdown-toggle btn-sm" type="button"
                                        data-bs-toggle="dropdown">
                                    განახლების ატვირთვა
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                       data-bs-target="#uploadProductExcelGlobalDistributinModal">Global Distribution</a>
                                </div>
                            </div>

                            <a href="{{ route('dashboard.product.create') }}" class="btn btn-icon btn-success btn-sm">
                                <i data-feather="plus-square"></i>
                            </a>
                            <button type="button" class="btn btn-icon btn-outline-primary btn-sm"
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
                                    <th>საიტზე</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($products as $product)
                                    <tr class="text-center">
                                        <td>
                                            <input type="checkbox" wire:model.live="selectedProducts"
                                                   value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            <img src="{{ asset('storage/' . $product->main_image) }}"
                                                 height="40" width="40"/>
                                        </td>
                                        <td class="text-start">
                                            <span class="badge badge-light-info">{{ $product->sku }}</span> -
                                            {{ $product->translations->where('locale', 'ka')->first()->title ?? ' ' }}
                                        </td>
                                        <td>
                                            @if(!empty($product->price->discount_price))
                                                <span class="badge badge-light-success">{{ $product->price->discount_price }} ₾</span><br><br>
                                                <span class="badge badge-light-danger">{{ $product->price->regular_price }} ₾</span>
                                            @else
                                                <span class="badge badge-light-success">{{ $product->price->regular_price ?? '' }} ₾</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($product->category->parent->translations))
                                                {{ $product->category->parent->translations->where('locale', 'ka')->first()->title ?? '' }}
                                            @endif
                                            @if(!empty($product->category->translations))
                                                / {{ $product->category->translations->where('locale', 'ka')->first()->title ?? '' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($product->brand->translations))
                                                {{ $product->brand->translations->where('locale', 'ka')->first()->title ?? '' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$product->trashed())
                                                <div class="form-check form-switch form-check-success d-flex justify-content-center">
                                                    <input type="checkbox" class="form-check-input"
                                                           id="active_{{ $product->id }}"
                                                           wire:click="toggleActive({{ $product->id }})"
                                                            @checked($product->active)/>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$product->trashed() && $product->active != 0)
                                                <div class="form-check form-switch form-check-success d-flex justify-content-center">
                                                    <input type="checkbox" class="form-check-input"
                                                           id="show_{{ $product->id }}"
                                                           wire:click="toggleShow({{ $product->id }})"
                                                            @checked($product->show)/>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->id !== 1)
                                                @if($product->trashed())
                                                    <a href="#" class="text-body"
                                                       wire:click="restoreModal({{ $product->id }})">
                                                        <i class="text-success" data-feather="rotate-ccw"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('dashboard.product.update', $product->id) }}"
                                                       class="text-body">
                                                        <i data-feather="edit"></i>
                                                    </a>
                                                    {{-- ✅ Quick Edit --}}
                                                    <a href="#" class="text-body"
                                                       wire:click="quickEditModal({{ $product->id }})">
                                                        <i class="text-warning" data-feather="zap"></i>
                                                    </a>
                                                    <a href="#" class="text-body"
                                                       wire:click="deleteModal({{ $product->id }})">
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
                            <div class="alert alert-warning">ჩამონათვალი ცარიელია!</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{ $products->links() }}
    </div>

    {{-- ✅ Quick Edit Modal --}}
    <div class="modal fade" wire:ignore.self id="quickEditModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">სწრაფი რედაქტირება</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">კატეგორია</label>
                        <select class="form-select" wire:model.live="quickEditCategoryId">
                            <option value="">— აირჩიეთ —</option>
                            @foreach($categories->where('parent_id', 0)->where('active', 1) as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!empty($quickEditSubcategories))
                        <div class="mb-2">
                            <label class="form-label">ქვეკატეგორია</label>
                            <select class="form-select" wire:model="quickEditSubcategoryId">
                                <option value="">— აირჩიეთ —</option>
                                @foreach($quickEditSubcategories as $sub)
                                    <option value="{{ $sub['id'] }}">{{ collect($sub['translations'] ?? [])->where('locale', 'ka')->first()['title'] ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-2">
                        <label class="form-label">ბრენდი</label>
                        <select class="form-select" wire:model="quickEditBrandId">
                            <option value="">— აირჩიეთ —</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ფასი <span class="text-danger">*</span></label>
                        <input type="number" step="0.01"
                               class="form-control @error('quickEditRegularPrice') border-danger is-invalid @enderror"
                               wire:model="quickEditRegularPrice" placeholder="0.00">
                        @error('quickEditRegularPrice')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ფასდაკლების ფასი</label>
                        <input type="number" step="0.01" class="form-control"
                               wire:model="quickEditDiscountPrice" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="quickEditSave">შენახვა</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Bulk Actions Modal --}}
    <div class="modal fade" wire:ignore.self id="bulkActionsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">მასობრივი მოქმედება ({{ count($selectedProducts) }} პროდუქტი)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- კატეგორია --}}
                    <p class="fw-semibold mb-1">კატეგორიის შეცვლა</p>
                    <div class="mb-2">
                        <select class="form-select" wire:model.live="bulkCategoryId">
                            <option value="">— კატეგორია —</option>
                            @foreach($categories->where('parent_id', 0)->where('active', 1) as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(!empty($bulkSubcategories))
                        <div class="mb-2">
                            <select class="form-select" wire:model="bulkSubcategoryId">
                                <option value="">— ქვეკატეგორია —</option>
                                @foreach($bulkSubcategories as $sub)
                                    <option value="{{ $sub['id'] }}">{{ collect($sub['translations'] ?? [])->where('locale', 'ka')->first()['title'] ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <button class="btn btn-primary btn-sm w-100 mb-3"
                            wire:click="bulkUpdateCategory">კატეგორიის განახლება</button>

                    <hr>

                    {{-- ბრენდი --}}
                    <p class="fw-semibold mb-1">ბრენდის შეცვლა</p>
                    <div class="mb-2">
                        <select class="form-select" wire:model="bulkBrandId">
                            <option value="">— ბრენდი —</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm w-100"
                            wire:click="bulkUpdateBrand">ბრენდის განახლება</button>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Excel Upload Modal --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self
         id="uploadProductExcelGlobalDistributinModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="uploadExcel">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">Excel-ის ატვირთვა</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">აირჩიეთ ფაილი (.xlsx)</label>
                        <input type="file"
                               class="form-control @error('excel_file') border-danger is-invalid @enderror"
                               wire:model="excel_file" accept=".xlsx,.xls">
                        @error('excel_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1" wire:loading.attr="disabled">
                            <span wire:loading.remove>ატვირთვა</span>
                            <span wire:loading><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Filter Modal --}}
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterProductModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="applyFilters">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">ფილტრი</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">საძიებო სიტყვა</label>
                        <input type="text" class="form-control" placeholder="დასახელება, ID, SKU"
                               wire:model.lazy="search_query"/>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">სორტირება</label>
                        <select class="form-select" wire:model.lazy="order_dir">
                            <option value="desc">ახალი</option>
                            <option value="asc">ძველი</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ჩვენება</label>
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
                                <option value="{{ $brand->id }}">{{ $brand->translations->where('locale', 'ka')->first()->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">მომწოდებელი</label>
                        <select class="form-select" wire:model.lazy="supplier_id">
                            <option value="">ყველა</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->translations->where('locale', 'ka')->first()->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ნაშთი</label>
                        <select class="form-select" wire:model.lazy="no_stock">
                            <option value="">ყველა</option>
                            <option value="0">ნულოვანი</option>
                            <option value="1">არსებული</option>
                        </select>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="status_active" wire:model.lazy="status_active">
                        <label class="form-check-label" for="status_active">მხოლოდ აქტიურები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="show_web" wire:model.lazy="show_web">
                        <label class="form-check-label" for="show_web">საიტზე ნაჩვენები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="with_trashed" wire:model.lazy="with_trashed">
                        <label class="form-check-label" for="with_trashed">წაშლილები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="unsorted" wire:model.lazy="unsorted">
                        <label class="form-check-label" for="unsorted">დაუხარისხებელი</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="draft" wire:model.lazy="draft">
                        <label class="form-check-label" for="draft">Draft</label>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1">გაფილტრე</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">გასუფთავება</button>
                    </div>
                </div>
            </form>
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
                if (result.isConfirmed) {
                    Livewire.dispatch('delete', {id: data[0].id});
                }
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
                if (result.isConfirmed) {
                    Livewire.dispatch('restore', {id: data[0].id});
                }
            });
        });

        Livewire.on('filter_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('filterProductModal'))?.hide();
        });

        Livewire.on('bulk_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('bulkActionsModal'))?.hide();
        });

        Livewire.on('quick_edit_modal_open', () => {
            new bootstrap.Modal(document.getElementById('quickEditModal')).show();
        });

        Livewire.on('quick_edit_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('quickEditModal'))?.hide();
        });
    </script>
@endsection