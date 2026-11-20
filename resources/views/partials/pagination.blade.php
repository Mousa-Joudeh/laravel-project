{{-- custom paginator: $spaces->links('partials.pagination') --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pages">
        @if ($paginator->onFirstPage())
            <span class="disabled"><i class="bi bi-arrow-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"><i class="bi bi-arrow-left"></i></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="gap">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"><i class="bi bi-arrow-right"></i></a>
        @else
            <span class="disabled"><i class="bi bi-arrow-right"></i></span>
        @endif
    </nav>
    <p class="page-info">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
@endif
