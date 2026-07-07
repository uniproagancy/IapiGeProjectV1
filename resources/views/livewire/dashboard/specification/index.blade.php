<div>
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">

                {{-- Stats --}}
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body d-flex align-items-center gap-2 py-1">
                                <div class="avatar bg-light-primary p-50">
                                    <div class="avatar-content"><i data-feather="list" class="font-medium-3"></i></div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $uniqueNames }}</h6>
                                    <small class="text-muted">უნიკალური სპეციფიკაცია</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body d-flex align-items-center gap-2 py-1">
                                <div class="avatar bg-light-success p-50">
                                    <div class="avatar-content"><i data-feather="filter" class="font-medium-3"></i></div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $filterEnabled }}</h6>
                                    <small class="text-muted">ფილტრი ჩართული</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body d-flex align-items-center gap-2 py-1">
                                <div class="avatar bg-light-secondary p-50">
                                    <div class="avatar-content"><i data-feather="database" class="font-medium-3"></i></div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $totalSpecs }}</h6>
                                    <small class="text-muted">სულ ჩანაწერი</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Main Card --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <h4 class="card-title mb-0">სპეციფიკაციების ფილტრები</h4>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            {{-- Search --}}
                            <div class="position-relative">
                                <input type="text"
                                       class="form-control form-control-sm"
                                       placeholder="ძიება..."
                                       wire:model.live.debounce.300ms="search_query"
                                       style="min-width: 200px;">
                            </div>

                            {{-- Filter Status --}}
                            <select class="form-select form-select-sm" wire:model.live="filter_status" style="width: auto;">
                                <option value="">ყველა</option>
                                <option value="1">ჩართული</option>
                                <option value="0">გამორთული</option>
                            </select>

                            {{-- Per Page --}}
                            <select class="form-select form-select-sm" wire:model.live="per_page" style="width: auto;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>

                            {{-- Bulk Actions --}}
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    მოქმედება
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item text-success" href="#"
                                       onclick="if(confirm('ყველა ფილტრი ჩაირთვება (მიმდინარე ძიების მიხედვით)?')) { @this.call('enableAll') }; return false;">
                                        <i data-feather="check-circle" class="me-1" style="width:14px;"></i>
                                        ყველას ჩართვა
                                    </a>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="if(confirm('ყველა ფილტრი გამოირთვება (მიმდინარე ძიების მიხედვით)?')) { @this.call('disableAll') }; return false;">
                                        <i data-feather="x-circle" class="me-1" style="width:14px;"></i>
                                        ყველას გამორთვა
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($specifications) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr class="text-center">
                                    <th class="text-start" style="width: 40%;">სპეციფიკაციის სახელი</th>
                                    <th>ჩანაწერები</th>
                                    <th>ფილტრში ჩართული</th>
                                    <th>სტატუსი</th>
                                    <th>ფილტრი</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($specifications as $spec)
                                    <tr class="text-center">
                                        <td class="text-start fw-bold">{{ $spec->name }}</td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $spec->total_count }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $spec->filter_count > 0 ? 'success' : 'secondary' }}">
                                                {{ $spec->filter_count }} / {{ $spec->total_count }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($spec->filter_count == $spec->total_count)
                                                <span class="badge badge-light-success">ჩართული</span>
                                            @elseif($spec->filter_count > 0)
                                                <span class="badge badge-light-warning">ნაწილობრივ</span>
                                            @else
                                                <span class="badge badge-light-secondary">გამორთული</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center">
                                                <div class="form-check form-switch form-check-success">
                                                    <input type="checkbox" class="form-check-input"
                                                           id="spec_filter_{{ md5($spec->name) }}"
                                                           wire:click="toggleFilter('{{ addslashes($spec->name) }}')"
                                                            @checked($spec->filter_count > 0) />
                                                    <label class="form-check-label" for="spec_filter_{{ md5($spec->name) }}">
                                                        <span class="switch-icon-left"><i data-feather="check"></i></span>
                                                        <span class="switch-icon-right"><i data-feather="x"></i></span>
                                                    </label>
                                                </div>
                                            </div>
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
                                    <span>სპეციფიკაციები ვერ მოიძებნა!</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{ $specifications->links() }}
            </div>
        </div>
    </div>
</div>