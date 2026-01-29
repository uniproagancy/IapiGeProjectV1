<div>
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">მომხმარებლის ჩამონათვალი</h4>
                        <div>
                            <button type="button" class="btn btn-icon btn-success mx-50" data-bs-toggle="modal"
                                    data-bs-target="#createUserModal">
                                <i data-feather="user-plus"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#filterUserModal">
                                <i data-feather="search"></i>
                            </button>
                        </div>
                    </div>
                    @if(count($users) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr class="text-center">
                                    <th>ID</th>
                                    <th>სახელი გვარი</th>
                                    <th>წვდომის ჯგუფი</th>
                                    <th>ელ-ფოსტა</th>
                                    <th>ტელეფონის ნომერი</th>
                                    <th>სტატუის</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $user)
                                    <tr class="text-center">
                                        <td>{{ $user->id }}</td>
                                        <td>
                                            <span @if($user->trashed()) class="badge badge-light-danger" @endif>{{ $user->name }} {{ $user->lastname }}</span>
                                        </td>
                                        <td><span class="badge badge-light-success">{{ $user->role->name }}</span></td>
                                        <td>
                                            {{ $user->email }}
                                            @if($user->verify_email === 1)
                                                <i class="text-success" data-feather="check-circle"></i>
                                            @else
                                                <i class="text-danger" data-feather="x-circle"></i>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $user->phone }}
                                            @if($user->verify_phone === 1)
                                                <i class="text-success" data-feather="check-circle"></i>
                                            @else
                                                <i class="text-danger" data-feather="x-circle"></i>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$user->trashed() && $user->role_id != 2)
                                                <div class="d-flex justify-content-center">
                                                    <div class="form-check form-switch form-check-success">
                                                        <input type="checkbox" class="form-check-input"
                                                               id="user_active_{{ $user->id }}"
                                                               wire:click="toggleActive({{ $user->id }})" @checked($user->active) />
                                                        <label class="form-check-label"
                                                               for="user_active_{{ $user->id }}">
                                                            <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                            <span class="switch-icon-right"><i
                                                                        data-feather="x"></i></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('dashboard.user.view', $user->id) }}" class="text-body">
                                                <i data-feather="user"></i>
                                            </a>
                                            @if($user->role_id !== 2)
                                                @if($user->trashed())
                                                    <a href="#" class="text-body"
                                                       wire:click="restoreModal({{ $user->id }})">
                                                        <i class="text-success" data-feather="rotate-ccw"></i>
                                                    </a>
                                                @else
                                                    <a href="#" class="text-body"
                                                       wire:click="deleteModal({{ $user->id }})">
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
        {{ $users->links() }}
    </div>
    <div class="modal modal-slide-in new-user-modal fade" wire:ignore.self id="filterUserModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="applyFilters" wire:keydown.enter="applyFilters">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">ფილტრი</h5>
                </div>
                <div class="modal-body flex-grow-1">
                    <div class="mb-1">
                        <label class="form-label">საძიებო სიტყვა</label>
                        <input type="text" class="form-control" placeholder="სახელი, გვარი, ელ-ფოსტა, ტელეფონის ნომერი"
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
                        <label class="form-label">წვდომის ჯგუფები</label>
                        <select class="form-select" wire:model.lazy="role_id">
                            <option value="0">ყველა ჯგუფი</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
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
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input" id="with_trashed"
                               wire:model.lazy="with_trashed">
                        <label class="form-check-label" for="with_trashed">წაშლილი ჩანაწერების ჩვენება</label>
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
    <livewire:dashboard.user.create/>
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
            const modalEl = document.getElementById('filterUserModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        Livewire.on('create_modal_close', () => {
            const modalEl = document.getElementById('createUserModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    </script>
@endsection