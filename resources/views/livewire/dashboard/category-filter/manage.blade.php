<div>
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <div>
                            <h4 class="card-title mb-0">კატეგორიის ფილტრები</h4>
                            <small class="text-muted">
                                აირჩიე, რომელი სპეციფიკაცია გამოჩნდეს საიტზე ფილტრად — თითო კატეგორიისთვის ცალკე
                            </small>
                        </div>
                        @if($this->category)
                            <button class="btn btn-sm btn-outline-danger"
                                    wire:click="disableAll"
                                    wire:confirm="ამ კატეგორიის ყველა ფილტრი გამოირთოს?">
                                ყველას გამორთვა
                            </button>
                        @endif
                    </div>

                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-6 mb-1">
                                <label class="form-label">კატეგორია</label>
                                <select class="form-select" wire:model.live="categoryId">
                                    <option value="">— აირჩიე კატეგორია —</option>
                                    @foreach($this->categories as $cat)
                                        <option value="{{ $cat->id }}">
                                            {{ $cat->parent_id ? '— ' : '' }}{{ $cat->translation('ka')?->title ?? ('#' . $cat->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if($this->category)
                                <div class="col-md-6 mb-1">
                                    <label class="form-label">ძებნა</label>
                                    <input type="text" class="form-control"
                                           wire:model.live.debounce.300ms="search"
                                           placeholder="მაგ. მოცულობა, ფართი, სიმძლავრე...">
                                </div>
                            @endif
                        </div>

                        @if(!$this->category)
                            <div class="alert alert-primary mb-0">
                                <div class="alert-body">აირჩიე კატეგორია, რომ მისი შესაძლო ფილტრები ნახო.</div>
                            </div>
                        @elseif($this->rows->isEmpty())
                            <div class="alert alert-warning mb-0">
                                <div class="alert-body">
                                    ამ კატეგორიაში გამოსადეგი სპეციფიკაცია ვერ მოიძებნა.
                                    შესაძლოა პროდუქტებს სპეციფიკაციები არ აქვთ შევსებული.
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th style="width:70px;">ფილტრი</th>
                                        <th>სპეციფიკაცია</th>
                                        <th style="width:110px;">პროდუქტი</th>
                                        <th style="width:100px;">ვარიანტი</th>
                                        <th>ნიმუში</th>
                                        <th style="width:210px;">ჩვენების სახელი</th>
                                        <th style="width:90px;">რიგი</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($this->rows as $row)
                                        <tr wire:key="spec-{{ md5($row['spec_name']) }}"
                                            class="{{ $row['enabled'] ? 'table-active' : '' }}">

                                            <td>
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input"
                                                           @checked($row['enabled'])
                                                           wire:click="toggle(@js($row['spec_name']))">
                                                </div>
                                            </td>

                                            <td>
                                                <span class="fw-bold">{{ $row['spec_name'] }}</span>
                                                @if($row['unit'])
                                                    <span class="badge bg-light-secondary ms-1">{{ $row['unit'] }}</span>
                                                @endif
                                            </td>

                                            <td>
                                                {{ $row['products'] }}
                                                <small class="text-muted">({{ $row['coverage'] }}%)</small>
                                            </td>

                                            <td>
                                                <span class="badge {{ $row['values'] > 30 ? 'bg-light-warning' : 'bg-light-success' }}">
                                                    {{ $row['values'] }}
                                                </span>
                                            </td>

                                            <td>
                                                <small class="text-muted">{{ $row['sample'] }}</small>
                                            </td>

                                            <td>
                                                @if($row['enabled'])
                                                    <input type="text" class="form-control form-control-sm"
                                                           value="{{ $row['label'] }}"
                                                           placeholder="{{ $row['spec_name'] }}"
                                                           wire:change="rename(@js($row['spec_name']), $event.target.value)">
                                                @else
                                                    <small class="text-muted">—</small>
                                                @endif
                                            </td>

                                            <td>
                                                @if($row['enabled'])
                                                    <button class="btn btn-sm btn-icon btn-flat-secondary"
                                                            wire:click="move(@js($row['spec_name']), -1)" title="ზემოთ">↑</button>
                                                    <button class="btn btn-sm btn-icon btn-flat-secondary"
                                                            wire:click="move(@js($row['spec_name']), 1)" title="ქვემოთ">↓</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>პროდუქტი</strong> — რამდენ პროდუქტს აქვს ეს სპეციფიკაცია შევსებული.
                                    <strong>ვარიანტი</strong> — რამდენი განსხვავებული მნიშვნელობა აქვს ნორმალიზაციის შემდეგ.
                                    ორივე რაც მეტია, ფილტრი მით უფრო სასარგებლოა; 30-ზე მეტი ვარიანტი კი ავტომატურად
                                    დიაპაზონებად დაჯგუფდება საიტზე.
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
