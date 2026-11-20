@extends('layouts.user')

@section('title', 'Studios')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/studio-companies.css') }}">
@endpush

@section('content')
    <div class="container studio-directory">
        <nav class="studio-directory-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Studios</span>
        </nav>

        <header class="studio-directory-header">
            <div>
                <h1>Explore <span class="serif">studios</span></h1>
                <p>Find verified studio companies and see what they offer.</p>
            </div>

            <form class="studio-directory-search" method="GET" action="{{ route('studio-companies.index') }}">
                <input type="search" name="search" value="{{ $search }}"
                       placeholder="Studio name or city" aria-label="Studio name or city" maxlength="100">
                <button class="btn btn-dark btn-sm" type="submit">Search</button>
            </form>
        </header>

        @error('search')
            <p role="alert">{{ $message }}</p>
        @enderror

        <div class="studio-company-grid">
            @forelse ($profiles as $profile)
                <a class="studio-company-card" href="{{ route('studio-companies.show', ['slug' => $profile->slug]) }}">
                    <img class="studio-company-cover"
                         src="{{ $profile->coverUrl() ?? asset('images/placeholder.svg') }}"
                         alt="" loading="lazy">

                    <div class="studio-company-body">
                        <div class="studio-company-logo">
                            @if ($profile->logoUrl())
                                <img src="{{ $profile->logoUrl() }}" alt="" loading="lazy">
                            @else
                                <span aria-hidden="true">{{ $profile->initials() }}</span>
                            @endif
                        </div>

                        <div class="studio-company-heading">
                            <h2>{{ $profile->company_name }}</h2>

                            @if ($profile->city)
                                <span><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $profile->city }}</span>
                            @endif
                        </div>

                        @if ($profile->tagline)
                            <p>{{ $profile->tagline }}</p>
                        @endif

                        <ul class="studio-company-counts">
                            <li><strong>{{ $profile->user->studios_count }}</strong> spaces</li>
                            <li><strong>{{ $profile->user->services_count }}</strong> services</li>
                            <li><strong>{{ $profile->user->packages_count }}</strong> packages</li>
                        </ul>
                    </div>
                </a>
            @empty
                <p>No studios found.</p>
            @endforelse
        </div>

        @if ($profiles->hasPages())
            <nav class="studio-directory-pagination" aria-label="Studio pages">
                @if ($profiles->previousPageUrl())
                    <a class="btn btn-light btn-sm" href="{{ $profiles->previousPageUrl() }}">Previous</a>
                @endif

                <span>Page {{ $profiles->currentPage() }} of {{ $profiles->lastPage() }}</span>

                @if ($profiles->nextPageUrl())
                    <a class="btn btn-light btn-sm" href="{{ $profiles->nextPageUrl() }}">Next</a>
                @endif
            </nav>
        @endif
    </div>
@endsection