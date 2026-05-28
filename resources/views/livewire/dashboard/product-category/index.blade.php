<div>
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">კატეგორიების ჩამონათვალი</h4>
                        <div>
                            <button type="button" class="btn btn-icon btn-success mx-50" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                <i data-feather="plus-square"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterCategoryModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                    @if(count($categories) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr class="text-center">
                                        <th>ID</th>
                                        <th class="text-start">დასახელება</th>
                                        <th>მშობელი კატეგორია</th>
                                        <th>SLUG</th>
                                        <th>სტატუსი</th>
                                        <th>მთავარ გვერდზე ჩვენება</th>
                                        <th>საიტზე ჩვენება</th>
                                        <th>მოქმედება</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($categories as $category)
                                    <tr class="text-center">
                                        <th>{{ $category->id }}</th>
                                        <td class="text-start">
                                            <span class="font-neue">
                                                {{ $category->translations->where('locale', 'ka')->first()?->title ?? 'დასახელება ცარიელია'  }}
                                            </span>
                                        </td>
                                        <td></td>
                                        <td>
                                            <a href="#" class="badge badge-light-primary">
                                                {{ $category->translations->where('locale', 'ka')->first()->slug }}
                                            </a>
                                        </td>
                                        <td>
                                            @if(!$category->trashed() && $category->id !== 1)
                                            <div class="d-flex justify-content-center">
                                                <div class="form-check form-switch form-check-success">
                                                    <input type="checkbox" class="form-check-input" id="category_active_{{ $category->id }}" wire:click="toggleActive({{ $category->id }})" @checked($category->active) />
                                                    <label class="form-check-label" for="category_active_{{ $category->id }}">
                                                        <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                        <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                    </label>
                                                </div>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$category->trashed() && $category->id !== 1 && $category->active != 0)
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input" id="category_show_on_main_{{ $category->id }}" wire:click="toggleShowOnMain({{ $category->id }})" @checked($category->show_on_main) />
                                                        <label class="form-check-label" for="category_show_on_main_{{ $category->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$category->trashed() && $category->id !== 1 && $category->active != 0)
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input" id="category_show_{{ $category->id }}" wire:click="toggleShow({{ $category->id }})" @checked($category->show) />
                                                        <label class="form-check-label" for="category_show_{{ $category->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($category->id !== 1)
                                                @if($category->trashed())
                                                    <a href="#" class="text-body" wire:click="restoreModal({{ $category->id }})">
                                                        <i class="text-success" data-feather="rotate-ccw"></i>
                                                    </a>
                                                @else
                                                    <a href="#" class="text-body" wire:click="openCategoryUpdateModal({{ $category->id }})">
                                                        <i data-feather="edit"></i>
                                                    </a>
                                                    <a href="#" class="text-body" wire:click="deleteModal({{ $category->id }})">
                                                        <i class="text-danger" data-feather="trash"></i>
                                                    </a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                    @if(!empty($category->children) && $category->active == 1)
                                        @foreach($category->children as $children)
                                        <tr class="text-center" style="background-color: #242b3d">
                                            <th>{{ $children->id }}</th>
                                            <td class="text-start ps-4">
                                                <span>-- {{ $children->translations->where('locale', 'ka')->first()->title ?? 'დასახელება ცარიელია'  }}</span>
                                            </td>
                                            <td>{{ $children->parent->translations->where('locale', 'ka')->first()->title ?? 'დასახელება ცარიელია'  }}</td>
                                            <td>
                                                <a href="#" class="badge badge-light-primary">
                                                    {{ $children->translations->where('locale', 'ka')->first()->slug }}
                                                </a>
                                            </td>
                                            <td>
                                                @if(!$children->trashed())
                                                    <div class="d-flex justify-content-center">
                                                        <div class="form-check form-switch form-check-success">
                                                            <input type="checkbox" class="form-check-input" id="category_active_{{ $children->id }}" wire:click="toggleActive({{ $children->id }})" @checked($children->active) />
                                                            <label class="form-check-label" for="category_active_{{ $children->id }}">
                                                                <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                                <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                            <td></td>
                                            <td>
                                                @if(!$children->trashed() && $children->active != 0)
                                                    <div class="d-flex justify-content-center">
                                                        <div class="form-check form-switch form-check-success">
                                                            <input type="checkbox" class="form-check-input" id="category_show_{{ $children->id }}" wire:click="toggleShow({{ $children->id }})" @checked($children->show) />
                                                            <label class="form-check-label" for="category_show_{{ $children->id }}">
                                                                <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                                <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($children->trashed())
                                                    <a href="#" class="text-body" wire:click="restoreModal({{ $children->id }})">
                                                        <i class="text-success" data-feather="rotate-ccw"></i>
                                                    </a>
                                                @else
                                                    <a href="#" class="text-body" wire:click="openCategoryUpdateModal({{ $children->id }})">
                                                        <i data-feather="edit"></i>
                                                    </a>
                                                    <a href="#" class="text-body" wire:click="deleteModal({{ $children->id }})">
                                                        <i class="text-danger" data-feather="trash"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
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
        {{ $categories->links() }}
    </div>
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterCategoryModal" tabindex="-1">
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
    <livewire:dashboard.product-category.create/>
    <livewire:dashboard.product-category.update/>
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
        const modalEl = document.getElementById('filterCategoryModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) {
            modal.hide();
        }
    });

    Livewire.on('create_modal_close', () => {
        const modalEl = document.getElementById('createCategoryModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) {
            modal.hide();
        }
    });

    Livewire.on('open-update-modal', () => {
        new bootstrap.Modal(document.getElementById('updateCategoryModal')).show();
    });

    Livewire.on('close-update-modal', () => {
        bootstrap.Modal.getInstance(document.getElementById('updateCategoryModal'))?.hide();
    });
</script>
@endsection