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
                            @if(!empty($selectedProducts))
                                <div class="btn-group">
                                    <button class="btn btn-icon btn-warning dropdown-toggle px-1" type="button"
                                            id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i data-feather="list"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <a class="dropdown-item" data-bs-toggle="modal"
                                           data-bs-target="#changeCategoryModal">კატეგორიის ცვლილება</a>
                                        <a class="dropdown-item" data-bs-toggle="modal"
                                           data-bs-target="#changeBrandModal">ბრენდის ცვლილება</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item text-danger" href="#"
                                           onclick="if(confirm('{{ count($selectedProducts) }} პროდუქტი წაიშლება. დარწმუნებული ხართ?')) { @this.call('bulkDelete') }; return false;">
                                            <i data-feather="trash-2" class="me-1" style="width:14px;"></i>
                                            წაშლა ({{ count($selectedProducts) }})
                                        </a>
                                        @if($with_trashed)
                                            <a class="dropdown-item text-success" href="#"
                                               onclick="if(confirm('{{ count($selectedProducts) }} პროდუქტი აღდგება. დარწმუნებული ხართ?')) { @this.call('bulkRestore') }; return false;">
                                                <i data-feather="rotate-ccw" class="me-1" style="width:14px;"></i>
                                                აღდგენა ({{ count($selectedProducts) }})
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            <div class="btn-group">
                                <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton3" data-bs-toggle="dropdown" aria-expanded="false">
                                    განახლების ატვირთვა
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton3">
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                       data-bs-target="#uploadProductExcelGlobalDistributinModal">Global Distribution</a>
                                </div>
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-icon btn-primary dropdown-toggle px-1" type="button"
                                        id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-feather="refresh-cw"></i>
                                </button>
                            </div>
                            <a href="{{ route('dashboard.product.create') }}" class="btn btn-icon btn-success mx-50"
                               style="font-size: 13px">
                                <i data-feather="plus-square"></i>
                            </a>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#filterProductModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                    @if(count($products) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr class="text-center">
                                    <th>
                                        <input type="checkbox" wire:model.live="selectAll" id="select-all">
                                    </th>
                                    <th>სურათი</th>
                                    <th class="text-start">დასახელება</th>
                                    <th>ფასი</th>
                                    <th>კატეგორია</th>
                                    <th>ბრენდი</th>
                                    <th>სტატუსი</th>
                                    <th>საიტზე ჩვენება</th>
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
                                            {{ $product->translations->where('locale', 'ka')->first()?->title ?? ' ' }}</td>
                                        <td>
                                            @if(!empty($product->price->discount_price))
                                                <span class="badge badge-light-success">{{ $product->price->discount_price ?? '' }} ₾</span>
                                                <br><br>
                                                <span class="badge badge-light-danger">{{ $product->price->regular_price ?? '' }} ₾</span>
                                            @else
                                                <span class="badge badge-light-success">{{ $product->price->regular_price ?? '' }} ₾</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($product->category?->parent?->translations))
                                                {{ $product->category->parent->translations->where('locale', 'ka')->first()?->title ?? '' }}
                                            @endif
                                            @if(!empty($product->category?->translations))
                                                /
                                                {{ $product->category->translations->where('locale', 'ka')->first()?->title ?? '' }}
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
                                                        <label class="form-check-label"
                                                               for="product_active_{{ $product->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i
                                                                        data-feather="x"></i></span>
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
                                                        <label class="form-check-label"
                                                               for="product_show_{{ $product->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i
                                                                        data-feather="x"></i></span>
                                                        </label>
                                                    </div>
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
                                                    <a href="#" class="text-body"
                                                       wire:click="priceEditModal({{ $product->id }})">
                                                        <i class="text-success" data-feather="dollar-sign"></i>
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
                            <div class="alert alert-warning" role="alert">
                                <div class="alert-body d-flex align-items-center">
                                    <i data-feather="alert-circle" class="me-50"></i>
                                    <span> ჩამონათვალი ცარიელია!</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{ $products->links() }}
    </div>
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="uploadProductExcelGlobalDistributinModal" tabindex="-1">
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
                               wire:model="excel_file"
                               accept=".xlsx,.xls">
                        @error('excel_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit"
                                class="btn btn-primary me-1"
                                wire:loading.attr="disabled">
                            <span wire:loading.remove>ატვირთვა</span>
                            <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span>
                        </span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
                        <input type="text" class="form-control" placeholder="დასახელება, ID, SKU"
                               wire:model.lazy="search_query"/>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">თარიღის სორტირება</label>
                        <select class="form-select" wire:model.lazy="order_dir">
                            <option value="desc">ახალ დამატებული</option>
                            <option value="asc">ძველ დამატებული</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">კატეგორია</label>
                        <select class="form-select" wire:model.lazy="category_id">
                            <option value="">აირჩიეთ კატეგორია</option>
                            @foreach($categories->where('parent_id', 0)->where('active', 1) as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->translations->where('locale','ka')->first()?->title ?? ('#' . $category->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ჩვენება</label>
                        <select class="form-select" wire:model.lazy="per_page">
                            <option value="10">10 ჩანაწერი</option>
                            <option value="25">25 ჩანაწერი</option>
                            <option value="50">50 ჩანაწერი</option>
                            <option value="100">100 ჩანაწერი</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">ბრენდი</label>
                        <select class="form-select" wire:model.lazy="brand_id">
                            <option value="">აირჩიეთ ბრენდი</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">
                                    {{ $brand->translations->where('locale','ka')->first()?->title ?? ('ბრენდი #' . $brand->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">მომწოდებელი</label>
                        <select class="form-select" wire:model.lazy="supplier_id">
                            <option value="">აირჩიეთ მომწოდებელი</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">
                                    {{ $supplier->translations->where('locale','ka')->first()?->name ?? ('#' . $supplier->id) }}
                                </option>
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
                        <input type="checkbox" class="form-check-input" id="status_active"
                               wire:model.lazy="status_active">
                        <label class="form-check-label" for="status_active">მხოლოდ აქტიურები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="show_web" wire:model.lazy="show_web">
                        <label class="form-check-label" for="show_web">მხოლოდ საიტზე ნაჩვენები</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="with_trashed"
                               wire:model.lazy="with_trashed">
                        <label class="form-check-label" for="with_trashed">წაშლილი ჩანაწერების ჩვენება</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="unsorted"
                               wire:model.lazy="unsorted">
                        <label class="form-check-label" for="unsorted">დაუხარისხებელი</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="no_brand"
                               wire:model.lazy="no_brand">
                        <label class="form-check-label" for="no_brand">ბრენდის გარეშე</label>
                    </div>
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="draft"
                               wire:model.lazy="draft">
                        <label class="form-check-label" for="draft">მხოლოდ Draft</label>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1">გაფილტრე</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">გასუფთავება
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" wire:ignore.self id="changeCategoryModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">პროდუქტების გადახარისხება</h5>
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
                    <button class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>
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
                        <input type="number"
                               step="0.01"
                               class="form-control @error('priceEditRegularPrice') border-danger is-invalid @enderror"
                               wire:model="priceEditRegularPrice"
                               placeholder="0.00">
                        @error('priceEditRegularPrice')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ფასდაკლების ფასი</label>
                        <input type="number"
                               step="0.01"
                               class="form-control"
                               wire:model="priceEditDiscountPrice"
                               placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="updatePrice">შენახვა</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" wire:ignore.self id="changeBrandModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">პროდუქტების გადახარისხება</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label"></label>
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
                    <button class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
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
            const modalEl = document.getElementById('filterProductModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('category_modal_close', () => {
            const modalEl = document.getElementById('changeCategoryModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('brand_modal_close', () => {
            const modalEl = document.getElementById('changeBrandModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('price_edit_modal_open', () => {
            const modalEl = document.getElementById('priceEditModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        Livewire.on('price_edit_modal_close', () => {
            const modalEl = document.getElementById('priceEditModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });
    </script>
@endsection