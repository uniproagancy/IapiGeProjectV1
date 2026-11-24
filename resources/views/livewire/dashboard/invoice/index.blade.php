<div>
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-body">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <h4 class="card-title mb-0">ინვოისები ({{ $invoices->total() }})</h4>
                        <div class="d-flex gap-1 align-items-center">
                            <input type="text" class="form-control" style="min-width: 260px;"
                                   wire:model.live.debounce.300ms="search"
                                   placeholder="ნომერი, კლიენტი, კოდი, ტელეფონი...">
                            <a href="{{ route('dashboard.invoice.create') }}" class="btn btn-primary text-nowrap">
                                + ახალი ინვოისი
                            </a>
                        </div>
                    </div>

                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ნომერი</th>
                                    <th>მომხმარებელი</th>
                                    <th>კოდი</th>
                                    <th>პოზიცია</th>
                                    <th class="text-end">თანხა</th>
                                    <th>თარიღი</th>
                                    <th>შექმნა</th>
                                    <th class="text-center">PDF</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($invoices as $invoice)
                                    <tr class="{{ session('invoice_created') === $invoice->number ? 'table-success' : '' }}">
                                        <td class="fw-bold text-nowrap">{{ $invoice->number }}</td>
                                        <td>
                                            {{ $invoice->customer_name }}
                                            @if($invoice->customer_phone)
                                                <br><small class="text-muted">{{ $invoice->customer_phone }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $invoice->customer_code ?: '—' }}</td>
                                        <td>{{ count($invoice->items ?? []) }}</td>
                                        <td class="text-end text-nowrap">{{ number_format($invoice->total, 2) }} ₾</td>
                                        <td class="text-nowrap">{{ $invoice->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            {{ $invoice->author ? trim($invoice->author->name . ' ' . $invoice->author->lastname) : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('dashboard.invoice.download', $invoice->id) }}"
                                               class="btn btn-sm btn-outline-primary">ჩამოტვირთვა</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-body">
                            {{ $invoices->links() }}
                        </div>
                    @else
                        <div class="card-body">
                            <div class="alert alert-primary mb-0">
                                <div class="alert-body">
                                    @if(trim($search) !== '')
                                        ძებნით არაფერი მოიძებნა.
                                    @else
                                        ინვოისი ჯერ არ შექმნილა.
                                        <a href="{{ route('dashboard.invoice.create') }}">შექმენი პირველი →</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
