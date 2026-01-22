<div wire:ignore.self class="modal fade" id="createCompanyModal" tabindex="-1" aria-labelledby="createCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="createCompanyModalLabel">კომპანიის დამატება</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form wire:submit.prevent="save">
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-1 col-12">
                            <label class="form-label" for="legal_id">სამართლებრივი ფორმა</label>
                            <select class="form-select @error('legal_id') border-danger is-invalid @enderror" id="legal_id" wire:model="legal_id">
                                <option value="0"></option>
                                @foreach($legals as $legal)
                                <option value="{{ $legal->id }}">{{ $legal->name }}</option>
                                @endforeach
                            </select>
                            @error('legal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-6">
                            <label class="form-label" for="name">დასახელება</label>
                            <input type="text" id="name" wire:model="name" class="form-control @error('name') border-danger is-invalid @enderror" autocomplete="off">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-6">
                            <label class="form-label" for="code">საიდენტიფიკაციო კოდი</label>
                            <input type="text" id="lastname" wire:model="code" class="form-control @error('code') border-danger is-invalid @enderror">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-6">
                            <label class="form-label" for="email">ელ-ფოსტა</label>
                            <input type="email" id="email" wire:model="email" class="form-control @error('email') border-danger is-invalid @enderror" autocomplete="off">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-6">
                            <label class="form-label" for="phone">ტელეფონის ნომერი</label>
                            <input type="text" id="phone" wire:model="phone" class="form-control @error('phone') border-danger is-invalid @enderror" autocomplete="off">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-1 col-12">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="user">წარმომადგენლის ელ-ფოსტა</label>
                                <small>წარმომადგენელი უნდა იყოს რეგისტრირებული როგორც მომხმარებელი!</small>
                            </div>
                            <input type="text" class="form-control @error('user') border-danger is-invalid @enderror" wire:model.live.300ms="user" autocomplete="off">
                            @error('user') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success" wire:loading.attr="disabled">შენახვა</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">დახურვა</button>
                </div>
            </form>
        </div>
    </div>
</div>