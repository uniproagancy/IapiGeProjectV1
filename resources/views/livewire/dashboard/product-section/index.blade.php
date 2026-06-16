<div>
    <div class="app-content content">
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">სექციები ({{ $sections->total() }})</h4>
                        <button class="btn btn-success" wire:click="openCreate">
                            <i data-feather="plus-square"></i> ახალი სექცია
                        </button>
                    </div>

                    @if(count($sections) > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr class="text-center">
                                    <th>სურათი</th>
                                    <th class="text-start">სათაური</th>
                                    <th>კატეგორია</th>
                                    <th>პროდუქტები</th>
                                    <th>მთავარზე</th>
                                    <th>სტატუსი</th>
                                    <th>მოქმედება</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($sections as $section)
                                    <tr class="text-center">
                                        <td>
                                            <img src="{{ $section->image ? asset('storage/' . $section->image) : asset('web-assets/img/no-product.png') }}"
                                                 height="40" width="40" style="object-fit:contain" alt="">
                                        </td>
                                        <td class="text-start">{{ $section->title }}</td>
                                        <td>
                                            {{-- კატეგორია: parent / sub --}}
                                            @if($section->category)
                                                @if($section->category->parent_id == 0)
                                                    <span class="text-muted">{{ $section->category->translations->where('locale','ka')->first()?->title ?? '—' }}</span>
                                                @else
                                                    <span class="text-muted small">{{ $section->category->parent?->translations->where('locale','ka')->first()?->title ?? '' }} /</span>
                                                    {{ $section->category->translations->where('locale','ka')->first()?->title ?? '—' }}
                                                @endif
                                            @else
                                                <span class="text-muted">გლობალური</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">{{ $section->products_count }}</span>
                                            <a href="{{ route('dashboard.sections.manage', $section->id) }}"
                                               class="btn btn-sm btn-outline-primary ms-1">ჩაყრა</a>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input"
                                                       wire:click="toggleHome({{ $section->id }})"
                                                        @checked($section->show_on_home)>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input"
                                                       wire:click="toggleActive({{ $section->id }})"
                                                        @checked($section->active)>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="#" class="text-body" wire:click="edit({{ $section->id }})">
                                                <i data-feather="edit"></i>
                                            </a>
                                            <a href="#" class="text-body" wire:click="deleteModal({{ $section->id }})">
                                                <i class="text-danger" data-feather="trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-2 pb-2">
                            <div class="alert alert-warning">
                                <div class="alert-body">სექციები არ არის.</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{ $sections->links() }}
    </div>

    {{-- შექმნა/რედაქტირება მოდალი --}}
    <div class="modal modal-slide-in fade" wire:ignore.self id="sectionModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content pt-0" wire:submit.prevent="save">
                <button type="button" class="btn-close" data-bs-dismiss="modal">×</button>
                <div class="modal-header mb-1">
                    <h5 class="modal-title">{{ $sectionId ? 'რედაქტირება' : 'ახალი სექცია' }}</h5>
                </div>
                <div class="modal-body flex-grow-1">

                    {{-- სათაური --}}
                    <div class="mb-1">
                        <label class="form-label">სათაური <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               wire:model="title"
                               placeholder="მაგ: საზაფხულო შემოთავაზება">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- მშობელი კატეგორია --}}
                    <div class="mb-1">
                        <label class="form-label">მშობელი კატეგორია</label>
                        <select class="form-select" wire:model.live="parent_category_id">
                            <option value="">— გლობალური (ყველა გვერდი) —</option>
                            @foreach($parentCategories as $cat)
                                <option value="{{ $cat->id }}">
                                    {{ $cat->translations->where('locale','ka')->first()?->title ?? ('#' . $cat->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ქვეკატეგორია (მხოლოდ თუ parent არჩეულია) --}}
                    @if($parent_category_id && !empty($subCategories))
                        <div class="mb-1">
                            <label class="form-label">ქვეკატეგორია <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror"
                                    wire:model="category_id">
                                <option value="">— აირჩიეთ ქვეკატეგორია —</option>
                                @foreach($subCategories as $sub)
                                    <option value="{{ $sub['id'] }}">
                                        {{ collect($sub['translations'] ?? [])->where('locale','ka')->first()['title'] ?? ('#' . $sub['id']) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @elseif($parent_category_id && empty($subCategories))
                        <div class="mb-1">
                            <div class="alert alert-info py-1 px-2" style="font-size:13px;">
                                ამ კატეგორიას ქვეკატეგორია არ აქვს — მშობელი კატეგორია შეინახება.
                            </div>
                        </div>
                    @endif

                    {{-- სურათი --}}
                    <div class="mb-1">
                        <label class="form-label">სურათი</label>
                        <input type="file"
                               class="form-control @error('image') is-invalid @enderror"
                               wire:model="image"
                               accept="image/*">
                        @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div wire:loading wire:target="image" class="text-muted small mt-1">იტვირთება...</div>

                        @if($image)
                            <img src="{{ $image->temporaryUrl() }}" height="60" class="mt-1" style="object-fit:contain">
                        @elseif($currentImage)
                            <img src="{{ asset('storage/' . $currentImage) }}" height="60" class="mt-1" style="object-fit:contain">
                        @endif
                    </div>

                    {{-- რიგითობა --}}
                    <div class="mb-1">
                        <label class="form-label">რიგითობა</label>
                        <input type="number" class="form-control" wire:model="sort_order">
                    </div>

                    {{-- მთავარ გვერდზე --}}
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input"
                               id="show_on_home" wire:model="show_on_home">
                        <label class="form-check-label" for="show_on_home">მთავარ გვერდზე ჩვენება</label>
                    </div>

                    {{-- აქტიური --}}
                    <div class="mb-1 form-check form-check-primary">
                        <input type="checkbox" class="form-check-input"
                               id="active" wire:model="active">
                        <label class="form-check-label" for="active">აქტიური</label>
                    </div>

                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-primary me-1"
                                wire:loading.attr="disabled">შენახვა</button>
                        <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">დახურვა</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@section('page_scripts')
    <script>
        Livewire.on('section_modal_open', () => {
            new bootstrap.Modal(document.getElementById('sectionModal')).show();
        });
        Livewire.on('section_modal_close', () => {
            bootstrap.Modal.getInstance(document.getElementById('sectionModal'))?.hide();
        });
        Livewire.on('swal:deleteModal', data => {
            Swal.fire({
                title: data[0].title, icon: data[0].icon,
                showCancelButton: true,
                confirmButtonText: data[0].confirmButtonText,
                cancelButtonText: data[0].cancelButtonText,
            }).then(r => {
                if (r.isConfirmed) Livewire.dispatch('delete', {id: data[0].id});
            });
        });
    </script>
@endsection