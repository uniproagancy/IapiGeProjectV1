<div wire:ignore.self class="modal fade" id="createBrandModal" tabindex="-1"
     aria-labelledby="createBrandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 1100px">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="createBrandModalLabel">კატეგორიის დამატება</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form wire:submit.prevent="save">
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-1 col-md-4">
                            <label class="form-label">დასახელება (ქართულად) <span class="text-danger">*</span></label>
                            <input type="text" wire:model="title_ka"
                                   class="form-control @error('title_ka') border-danger is-invalid @enderror">
                            @error('title_ka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">დასახელება (ინგლისურად)</label>
                            <input type="text" wire:model="title_en"
                                   class="form-control @error('title_en') border-danger is-invalid @enderror">
                            @error('title_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">დასახელება (რუსულად)</label>
                            <input type="text" wire:model="title_ru"
                                   class="form-control @error('title_ru') border-danger is-invalid @enderror">
                            @error('title_ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta keywords (ქართულად)</label>
                            <textarea class="form-control @error('keywords_ka') border-danger is-invalid @enderror"
                                      wire:model="keywords_ka"></textarea>
                            @error('keywords_ka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta keywords (ინგლისურად)</label>
                            <textarea class="form-control @error('keywords_en') border-danger is-invalid @enderror"
                                      wire:model="keywords_en"></textarea>
                            @error('keywords_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta keywords (რუსულად)</label>
                            <textarea class="form-control @error('keywords_ru') border-danger is-invalid @enderror"
                                      wire:model="keywords_ru"></textarea>
                            @error('keywords_ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta description (ქართულად)</label>
                            <textarea class="form-control @error('description_ka') border-danger is-invalid @enderror"
                                      wire:model="description_ka"></textarea>
                            @error('description_ka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta description (ინგლისურად)</label>
                            <textarea class="form-control @error('description_en') border-danger is-invalid @enderror"
                                      wire:model="description_en"></textarea>
                            @error('description_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-md-4">
                            <label class="form-label">Meta description (რუსულად)</label>
                            <textarea class="form-control @error('description_ru') border-danger is-invalid @enderror"
                                      wire:model="description_ru"></textarea>
                            @error('description_ru') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" wire:loading.attr="disabled">
                        შენახვა
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        დახურვა
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
