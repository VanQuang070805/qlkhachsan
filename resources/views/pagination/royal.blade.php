@if ($paginator->hasPages())
    <nav class="royal-pagination" aria-label="Phân trang">
        <ul>
            <li>
                @if ($paginator->onFirstPage())
                    <span class="royal-pagination__control is-disabled" aria-disabled="true"><i class="bi bi-chevron-left" aria-hidden="true"></i><span>Trước</span></span>
                @else
                    <a class="royal-pagination__control" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left" aria-hidden="true"></i><span>Trước</span></a>
                @endif
            </li>
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="royal-pagination__ellipsis" aria-hidden="true">…</span><span class="visually-hidden">Nhiều trang hơn</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <a class="royal-pagination__page is-current" href="{{ $url }}" aria-current="page">{{ $page }}</a>
                            @else
                                <a class="royal-pagination__page" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach
            <li>
                @if ($paginator->hasMorePages())
                    <a class="royal-pagination__control" href="{{ $paginator->nextPageUrl() }}" rel="next"><span>Sau</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                @else
                    <span class="royal-pagination__control is-disabled" aria-disabled="true"><span>Sau</span><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
