<div>
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">კომპანიების ჩამონათვალი</h4>
                        <div>
                            <button type="button" class="btn btn-icon btn-success mx-50" data-bs-toggle="modal"
                                    data-bs-target="#createCompanyModal">
                                <i data-feather="user-plus"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#filterCompanyModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                    @if(count($companies) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr class="text-center">
                                    <th>ID</th>
                                    <th>დასახელება - საიდენტიფიკაციო კოდი</th>
                                    <th>წარმომადგენელი</th>
                                    <th>ელ-ფოსტა</th>
                                    <th>ტელეფონის ნომერი</th>
                                    <th>სტატუის</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($companies as $company)
                                    <tr class="text-center">
                                        <td>{{ $company->id }}</td>
                                        <td>
                                            <span @if($company->trashed()) class="badge badge-light-danger" @endif>{{ $company->legal->name }} {{ $company->name }} - <span
                                                        class="badge badge-light-success">{{ $company->code }}</span></span>
                                        </td>
                                        <td>
                                            <a href="{{ route('dashboard.user.view', $company->user_id) }}"
                                               class="badge @if($company->user->trashed()) badge-light-danger @else badge-light-info @endif">
                                                {{ $company->user->name }} {{ $company->user->lastname }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $company->email }}
                                        </td>
                                        <td>
                                            {{ $company->phone }}
                                        </td>
                                        <td>
                                            @if(!$company->trashed() && !$company->user->trashed())
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input"
                                                               id="company_active_{{ $company->id }}"
                                                               wire:click="toggleActive({{ $company->id }})" @checked($company->active) />
                                                        <label class="form-check-label"
                                                               for="company_active_{{ $company->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i
                                                                        data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="" class="text-body">
                                                <i data-feather="user"></i>
                                            </a>
                                            @if(!$company->user->trashed())
                                                @if($company->trashed())
                                                    <a href="#" class="text-body"
                                                       wire:click="restoreModal({{ $company->id }})">
                                                        <i class="text-success" data-feather="rotate-ccw"></i>
                                                    </a>
                                                @else
                                                    <a href="#" class="text-body"
                                                       wire:click="deleteModal({{ $company->id }})">
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
        {{ $companies->links() }}
    </div>
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterCompanyModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="applyFilters" wire:keydown.enter="applyFilters">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">ფილტრი</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">საძიებო სიტყვა</label>
                        <input type="text" class="form-control"
                               placeholder="დასახელება, საიდენტიფიკაციო კოდი, წარმომადგენელი, ელ-ფოსტა, ტელეფონის ნომერი"
                               wire:model.lazy="search_query"/>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">სამართლებრივი ფორმა</label>
                        <select class="form-select" wire:model.lazy="legal_id">
                            <option value="0">ყველა ფორმა</option>
                            @foreach($legal_forms as $legal_form)
                                <option value="{{ $legal_form->id }}">{{ $legal_form->name }}</option>
                            @endforeach
                        </select>
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
                        <input type="checkbox" class="form-check-input" id="withTrashed" wire:model.lazy="with_trashed">
                        <label class="form-check-label" for="withTrashed">წაშლილი ჩანაწერების ჩვენება</label>
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
    <livewire:dashboard.company.create/>
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
            const modalEl = document.getElementById('filterCompanyModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('create_modal_close', () => {
            const modalEl = document.getElementById('createCompanyModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    </script>
@endsection