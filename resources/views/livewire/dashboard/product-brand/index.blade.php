<div>
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">ბრენდების ჩამონათვალი</h4>
                        <div>
                            <button type="button" class="btn btn-icon btn-success mx-50" data-bs-toggle="modal" data-bs-target="#createBrandModal">
                                <i data-feather="plus-square"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterBrandModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                    @if(count($brands) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr class="text-center">
                                    <th>ID</th>
                                    <th>დასახელება</th>
                                    <th>SLUG</th>
                                    <th>სტატუსი</th>
                                    <th>საიტზე ჩვენება</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($brands as $brand)
                                    <tr class="text-center">
                                        <th>{{ $brand->id }}</th>
                                        <td>
                                            <span>
                                                {{ $brand->translations->where('locale', 'ka')->first()?->title ?? 'დასახელება ცარიელია'  }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="#" class="badge badge-light-primary">
                                                {{ $brand->translations->where('locale', 'ka')->first()->slug ?? '' }}
                                            </a>
                                        </td>
                                        <td>
                                            @if(!$brand->trashed())
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input" id="brand_active{{ $brand->id }}" wire:click="toggleActive({{ $brand->id }})" @checked($brand->active) />
                                                        <label class="form-check-label" for="brand_active{{ $brand->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$brand->trashed() && $brand->active != 0)
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input" id="brand_show_{{ $brand->id }}" wire:click="toggleShow({{ $brand->id }})" @checked($brand->show) />
                                                        <label class="form-check-label" for="brand_show_{{ $brand->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($brand->trashed())
                                                <a href="#" class="text-body" wire:click="restoreModal({{ $brand->id }})">
                                                    <i class="text-success" data-feather="rotate-ccw"></i>
                                                </a>
                                            @else
                                                <a href="#" class="text-body" wire:click="openBrandUpdateModal({{ $brand->id }})">
                                                    <i data-feather="edit"></i>
                                                </a>
                                                <a href="#" class="text-body" wire:click="deleteModal({{ $brand->id }})">
                                                    <i class="text-danger" data-feather="trash"></i>
                                                </a>
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
        {{ $brands->links() }}
    </div>
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterBrandModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="applyFilters" wire:keydown.enter="applyFilters">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">ფილტრი</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">საძიებო სიტყვა</label>
                        <input type="text" class="form-control" placeholder="დასახელება, SLUG, ID"
                               wire:model.lazy="search_query" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">თარიღის სორტირება</label>
                        <select class="form-select" wire:model.lazy="order_dir">
                            <option value="desc">ახალ დამატებული</option>
                            <option value="asc">ძველ დამატებული</option>
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
                        <label class="form-check-label" for="with_trashed">წაშლილი ჩანაწერების ჩვენება</label>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1">გაფილტრე</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">გასუფთავება</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <livewire:dashboard.product-brand.create/>
    <livewire:dashboard.product-brand.update/>
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
                    Livewire.dispatch('delete', { id: data[0].id });
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
                    Livewire.dispatch('restore', { id: data[0].id });
                }
            });
        });

        Livewire.on('filter_modal_close', () => {
            const modalEl = document.getElementById('filterBrandModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if(modal) {
                modal.hide();
            }
        });

        Livewire.on('create_modal_close', () => {
            const modalEl = document.getElementById('filterBrandModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if(modal) {
                modal.hide();
            }
        });
    </script>
@endsection