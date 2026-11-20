@props(['records', 'id' => 'sp-record-table'])
<div class="sp-data-table" id="{{ $id }}">
    <div class="sp-table-wrap">
        <table class="sp-table sp-management-table">
            <thead>{{ $head }}</thead>
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
    <div class="sp-list-footer">
        <label class="sp-dense-control">
            <input type="checkbox" data-table-dense>
            <span class="sp-switch-track"></span>
            <span>Dense</span>
        </label>
        <div class="sp-footer-right">
            <form method="GET" action="{{ url()->current() }}" class="sp-per-page-form">
                @foreach(request()->except('page', 'per_page') as $key => $value)
                    @if(is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="{{ $id }}-size">Rows per page:</label>
                <select id="{{ $id }}-size" name="per_page" class="sp-per-page-select" data-table-page-size>
                    @foreach([5, 10, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($records->perPage() === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
            <span class="sp-range-label">{{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ $records->total() }}</span>
            <div class="sp-footer-pagination">
                @if($records->previousPageUrl())
                    <a href="{{ $records->previousPageUrl() }}" class="sp-icon-button" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
                @else
                    <span class="sp-icon-button sp-icon-disabled" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                @endif
                @if($records->nextPageUrl())
                    <a href="{{ $records->nextPageUrl() }}" class="sp-icon-button" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="sp-icon-button sp-icon-disabled" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                @endif
            </div>
        </div>
    </div>
</div>
