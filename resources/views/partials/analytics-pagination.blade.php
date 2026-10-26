@if ($paginator->total() > 0)
    <nav class="pagination" aria-label="{{ $label }} pagination">
        <span class="muted">Showing {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }} {{ $label }}</span>
        @if ($paginator->hasPages())
            <div class="pagination-links">
                @if ($paginator->onFirstPage())
                    <span class="button disabled" aria-disabled="true">Previous</span>
                @else
                    <a class="button" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
                @endif
                @if ($paginator->hasMorePages())
                    <a class="button" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="button disabled" aria-disabled="true">Next</span>
                @endif
            </div>
        @endif
    </nav>
@endif
