@extends('layouts.user')

@section('title', 'Packages')

@push('styles')
    <link href="{{ asset('css/studios.css') }}" rel="stylesheet">
    <link href="{{ asset('css/packages.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container">

        <div class="page-head">
            <nav class="crumbs"><a href="{{ url('/') }}">Home</a> <i class="bi bi-chevron-right"></i> <span>Packages</span></nav>
            <h1>Ready-made <span class="serif">packages</span></h1>
            <p>Everything for the day in one price: the space, the photographers, the lights and the edit.</p>

            <form action="{{ route('packages.index') }}" method="GET" class="searchbar">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search packages, e.g. wedding">
                <button type="submit" class="btn btn-dark btn-sm">Search</button>
            </form>
        </div>

        <div class="listing">

            
            <aside class="filters">
                <form action="{{ route('packages.index') }}" method="GET" id="filters-form">
                    <input type="hidden" name="q" value="{{ request('q') }}">

                    <div class="filter-group">
                        <h6>Package type</h6>
                        @foreach ($types as $key => $label)
                            <label class="check">
                                <input type="checkbox" name="types[]" value="{{ $key }}" {{ in_array($key, request('types', [])) ? 'checked' : '' }}>
                                {{ $label }} packages
                                <span class="count">{{ $typeCounts[$key] ?? 0 }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="filter-group">
                        <h6>Price</h6>
                        <label class="check"><input type="radio" name="sort" value="" {{ request('sort') ? '' : 'checked' }}> Newest first</label>
                        <label class="check"><input type="radio" name="sort" value="price_desc" {{ request('sort') == 'price_desc' ? 'checked' : '' }}> Highest price first</label>
                        <label class="check"><input type="radio" name="sort" value="price_asc" {{ request('sort') == 'price_asc' ? 'checked' : '' }}> Lowest price first</label>
                    </div>

                    <div class="filter-group">
                        <h6>Budget</h6>
                        <select name="max_price" class="select">
                            <option value="">Any budget</option>
                            <option value="100" {{ request('max_price') == 100 ? 'selected' : '' }}>Up to JD 100</option>
                            <option value="200" {{ request('max_price') == 200 ? 'selected' : '' }}>Up to JD 200</option>
                            <option value="400" {{ request('max_price') == 400 ? 'selected' : '' }}>Up to JD 400</option>
                            <option value="800" {{ request('max_price') == 800 ? 'selected' : '' }}>Up to JD 800</option>
                        </select>
                    </div>

                    <a href="{{ route('packages.index') }}" class="btn btn-light btn-block"><i class="bi bi-arrow-counterclockwise"></i> Reset filters</a>
                </form>
            </aside>

            
            <section>
                <p class="results-count"><b>{{ $packages->total() }}</b> packages</p>

                @if ($packages->count() > 0)
                    <div class="grid grid-3">
                        @foreach ($packages as $package)
                            @include('customer.packages.card', ['package' => $package])
                        @endforeach
                    </div>

                    @if ($packages->lastPage() > 1)
                        <nav class="pages">
                            @if ($packages->currentPage() > 1)
                                <a href="{{ $packages->previousPageUrl() }}"><i class="bi bi-arrow-left"></i></a>
                            @endif
                            @for ($i = 1; $i <= $packages->lastPage(); $i++)
                                <a href="{{ $packages->url($i) }}" class="{{ $i == $packages->currentPage() ? 'current' : '' }}">{{ $i }}</a>
                            @endfor
                            @if ($packages->hasMorePages())
                                <a href="{{ $packages->nextPageUrl() }}"><i class="bi bi-arrow-right"></i></a>
                            @endif
                        </nav>
                        <p class="page-info">Showing {{ $packages->firstItem() }}–{{ $packages->lastItem() }} of {{ $packages->total() }}</p>
                    @endif
                @else
                    <div class="empty">
                        <i class="bi bi-box2-heart"></i>
                        <h4>No packages found</h4>
                        <p>Try another type or a bigger budget.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/packages.js') }}"></script>
@endpush