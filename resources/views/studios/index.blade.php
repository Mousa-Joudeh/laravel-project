@extends('layouts.user')

@section('title', 'Spaces')

@push('styles')
    <link href="{{ asset('css/studios.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container">

      
        <div class="page-head">
            <div class="head-row">
                <div>
                    <h1>Spaces <span class="serif muted">to create in</span></h1>
                    <p>Studios and creative rooms you can book by the hour.</p>
                </div>

                @if (Auth::check() && Auth::user()->role === 'provider')
                    <a href="{{ route('studios.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add a space</a>
                @endif
            </div>

            <form action="{{ route('studios.index') }}" method="GET" class="searchbar">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by studio name or city">
                <button type="submit" class="btn btn-dark btn-sm">Search</button>
            </form>
        </div>

        <div class="listing">

            
            <aside class="filters">
                <form action="{{ route('studios.index') }}" method="GET" id="filters-form">
                    <input type="hidden" name="q" value="{{ request('q') }}">

                    <div class="filter-group">
                        <h6>Studio type</h6>
                        <label class="check">
                            <input type="radio" name="type" value="" {{ request('type') ? '' : 'checked' }}> All types
                        </label>
                        @foreach ($studioTypes as $type)
                            <label class="check">
                                <input type="radio" name="type" value="{{ $type->id }}" {{ request('type') == $type->id ? 'checked' : '' }}>
                                {{ $type->name }}
                            </label>
                        @endforeach
                    </div>

                    <div class="filter-group">
                        <h6>City</h6>
                        <select name="city" class="select">
                            <option value="">All Jordan</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city }}" {{ request('city') == $city ? 'selected' : '' }}>{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-group">
                        <h6>Capacity</h6>
                        <select name="capacity" class="select">
                            <option value="">Any</option>
                            <option value="2" {{ request('capacity') == 2 ? 'selected' : '' }}>2+ people</option>
                            <option value="5" {{ request('capacity') == 5 ? 'selected' : '' }}>5+ people</option>
                            <option value="10" {{ request('capacity') == 10 ? 'selected' : '' }}>10+ people</option>
                            <option value="20" {{ request('capacity') == 20 ? 'selected' : '' }}>20+ people</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h6>Price</h6>
                        <select name="sort" class="select">
                            <option value="">Newest first</option>
                            <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Lowest price first</option>
                            <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Highest price first</option>
                        </select>
                    </div>

                    <a href="{{ route('studios.index') }}" class="btn btn-light btn-block"><i class="bi bi-arrow-counterclockwise"></i> Reset filters</a>
                </form>
            </aside>

            
            <section>
                <p class="results-count"><b>{{ $studios->total() }}</b> spaces found</p>

                @if ($studios->count() > 0)
                    <div class="grid grid-3">
                        @foreach ($studios as $studio)
                            <article class="space-card">
                                @include('partials.favorite-heart', ['type' => 'studio', 'id' => $studio->id])
                                <div class="media slider">
                                    <a href="{{ route('studios.show', $studio->id) }}" class="slides">
                                        @forelse ($studio->images as $image)
                                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="{{ $studio->name }}">
                                        @empty
                                            <img src="{{ asset('images/placeholder.svg') }}" alt="">
                                        @endforelse
                                    </a>

                                    @if ($studio->type)
                                        <span class="tag tag-float"><i class="bi {{ $studio->type->icon }}"></i> {{ $studio->type->name }}</span>
                                    @endif

                                    {{-- status badge for the admin and the owner provider --}}
                                    @if (Auth::check() && Auth::user()->role !== 'customer' && $studio->status !== 'approved')
                                        <span class="badge badge-{{ $studio->status }} status-float">{{ ucfirst($studio->status) }}</span>
                                    @endif

                                    @if ($studio->images->count() > 1)
                                        <button type="button" class="slide-btn prev"><i class="bi bi-chevron-left"></i></button>
                                        <button type="button" class="slide-btn next"><i class="bi bi-chevron-right"></i></button>
                                    @endif
                                </div>

                                <div class="card-body">
                                    <div class="card-line">
                                        <h3 class="card-title"><a href="{{ route('studios.show', $studio->id) }}">{{ $studio->name }}</a></h3>
                                        @if ($studio->reviews_total > 0)
                                            <span class="rating"><i class="bi bi-star-fill"></i> {{ $studio->rating }} <small>({{ $studio->reviews_total }})</small></span>
                                        @else
                                            <span class="rating muted"><i class="bi bi-stars"></i> New</span>
                                        @endif
                                    </div>
                                    <div class="card-line">
                                        <span class="muted"><i class="bi bi-geo-alt"></i> {{ $studio->city }}</span>
                                        <span class="price">JD {{ number_format($studio->hourly_price, 0) }} <small>/ hour</small></span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                   
                    @if ($studios->lastPage() > 1)
                        <nav class="pages">
                            @if ($studios->currentPage() > 1)
                                <a href="{{ $studios->previousPageUrl() }}"><i class="bi bi-arrow-left"></i></a>
                            @endif

                            @for ($i = 1; $i <= $studios->lastPage(); $i++)
                                <a href="{{ $studios->url($i) }}" class="{{ $i == $studios->currentPage() ? 'current' : '' }}">{{ $i }}</a>
                            @endfor

                            @if ($studios->hasMorePages())
                                <a href="{{ $studios->nextPageUrl() }}"><i class="bi bi-arrow-right"></i></a>
                            @endif
                        </nav>
                    @endif
                @else
                    <div class="empty">
                        <i class="bi bi-search"></i>
                        <h4>No spaces match these filters</h4>
                        <p>Try another city, a smaller capacity or another type.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/studios.js') }}"></script>
@endpush