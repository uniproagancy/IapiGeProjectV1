@if ($paginator->hasPages())
    <div>
        <nav aria-label="Page navigation">
            <ul class="pagination mt-2 justify-content-end" wire:key="pagination-{{ $paginator->currentPage() }}">
                @if ($paginator->onFirstPage())
                    <li class="page-item prev-item disabled">
                        <span class="page-link"></span>
                    </li>
                @else
                    <li class="page-item prev-item">
                        <button class="page-link" wire:click="previousPage" wire:loading.attr="disabled"></button>
                    </li>
                @endif
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <button class="page-link" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach
                @if ($paginator->hasMorePages())
                    <li class="page-item next-item">
                        <button class="page-link" wire:click="nextPage" wire:loading.attr="disabled"></button>
                    </li>
                @else
                    <li class="page-item next-item disabled">
                        <span class="page-link"></span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif