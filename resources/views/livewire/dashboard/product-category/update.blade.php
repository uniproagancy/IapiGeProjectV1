<div wire:ignore.self class="modal fade" id="updateCategoryModal" tabindex="-1"
     aria-labelledby="updateCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 1100px">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="updateCategoryModalLabel">კატეგორიის რედაქტირება</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="save">
                <div class="modal-body">
                    <div class="row">

                        {{-- მშობელი კატეგორია --}}
                        <div class="mb-1 col-12">
                            <label class="form-label">მშობელი კატეგორია</label>
                            <select class="form-select" wire:model="parent_id">
                                <option value="0">— მთავარი კატეგორია —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ $cat->translations->where('locale', 'ka')->first()->title ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- სახელები --}}
                        <div class="mb-1 col-md-4">
                            <label class="form-label">დასახელება (ქართულად) <span class="text-danger">*</span></label>
                            <input type="text" wire:model="title_ka"
                                   class="form-control @error('title_ka') is-invalid @enderror">
                            @error('title_ka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">დასახელება (ინგლისურად)</label>
                            <input type="text" wire:model="title_en"
                                   class="form-control @error('title_en') is-invalid @enderror">
                            @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">დასახელება (რუსულად)</label>
                            <input type="text" wire:model="title_ru"
                                   class="form-control @error('title_ru') is-invalid @enderror">
                            @error('title_ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Keywords --}}
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta keywords (ქართულად)</label>
                            <textarea class="form-control @error('keywords_ka') is-invalid @enderror"
                                      wire:model="keywords_ka"></textarea>
                            @error('keywords_ka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta keywords (ინგლისურად)</label>
                            <textarea class="form-control @error('keywords_en') is-invalid @enderror"
                                      wire:model="keywords_en"></textarea>
                            @error('keywords_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta keywords (რუსულად)</label>
                            <textarea class="form-control @error('keywords_ru') is-invalid @enderror"
                                      wire:model="keywords_ru"></textarea>
                            @error('keywords_ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta description (ქართულად)</label>
                            <textarea class="form-control @error('description_ka') is-invalid @enderror"
                                      wire:model="description_ka"></textarea>
                            @error('description_ka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta description (ინგლისურად)</label>
                            <textarea class="form-control @error('description_en') is-invalid @enderror"
                                      wire:model="description_en"></textarea>
                            @error('description_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta description (რუსულად)</label>
                            <textarea class="form-control @error('description_ru') is-invalid @enderror"
                                      wire:model="description_ru"></textarea>
                            @error('description_ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- სორტირება და სტატუსი --}}
                        <div class="mb-1 col-md-4">
                            <label class="form-label">სორტირება</label>
                            <input type="number" wire:model="sortable" class="form-control" placeholder="0">
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">სტატუსი</label>
                            <select class="form-select" wire:model="active">
                                <option value="1">აქტიური</option>
                                <option value="0">არააქტიური</option>
                            </select>
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">საიტზე ჩვენება</label>
                            <select class="form-select" wire:model="show">
                                <option value="1">ჩვენება</option>
                                <option value="0">არ ჩვენება</option>
                            </select>
                        </div>

                        {{-- ✅ Alta / Zoommer mapping --}}
                        <div class="col-12 mt-2 mb-1">
                            <hr>
                            <p class="fw-semibold mb-1">მომწოდებლის კატეგორიების მიბმა</p>
                        </div>
                        <div class="mb-1 col-md-6">
                            <label class="form-label">Alta კატეგორიის ID</label>
                            <input type="number"
                                   class="form-control"
                                   wire:model="alta_category_id"
                                   placeholder="Alta-ს categoryId (მაგ: 16)">
                            <small class="text-muted">Alta API-ს categoryId — პროდუქტი ავტომატურად ამ კატეგორიაში ჩავარდება</small>
                        </div>
                        <div class="mb-1 col-md-6">
                            <label class="form-label">Zoommer კატეგორიის ID</label>
                            <input type="number"
                                   class="form-control"
                                   wire:model="zoommer_category_id"
                                   placeholder="Zoommer-ის categoryId">
                            <small class="text-muted">Zoommer API-ს categoryId — პროდუქტი ავტომატურად ამ კატეგორიაში ჩავარდება</small>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>განახლება</span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm"></span>
                            ინახება...
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        დახურვა
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>