@extends('layouts.dashboard')

@section('title', 'Package Management')

@section('actions')
    <a href="{{ route('provider.packages.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New package</a>
@endsection

@section('content')
    <div class="dash-head">
        <div>
            <h1>Your packages</h1>
            <p>Bundle a space and services in one fixed price.</p>
        </div>
    </div>

    @if ($packages->isEmpty())
        <x-empty icon="bi-box2-heart" title="No packages yet" text="Create a wedding, graduation or product package.">
            <a href="{{ route('provider.packages.create') }}" class="btn btn-primary btn-sm">Create a package</a>
        </x-empty>
    @else
        <div class="grid grid-3">
            @foreach ($packages as $package)
                <article class="package-card">
                    <a href="{{ route('provider.packages.show', $package) }}" class="media"><img src="{{ $package->coverUrl() }}" alt=""></a>
                    <div class="card-body">
                        <div class="card-line" style="margin-top:0">
                            <span class="tag">{{ $package->typeLabel() }}</span>
                            @if ($package->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-draft">Off</span>
                            @endif
                        </div>
                        <h3 class="card-title" style="margin-top:8px"><a href="{{ route('provider.packages.show', $package) }}">{{ $package->name }}</a></h3>
                        <div class="studio-line"><i class="bi bi-camera"></i> {{ $package->studio?->name ?? 'On location' }} · {{ $package->services->count() }} services</div>
                        <div class="foot">
                            <span class="price">JD {{ number_format($package->price, 0) }}</span>
                            <div class="table-actions">
                                <x-rating :avg="$ratings->get($package->id)?->avg" :count="$ratings->get($package->id)?->cnt ?? 0" />
                                <a href="{{ route('provider.packages.edit', $package) }}" class="icon-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('provider.packages.destroy', $package) }}" data-confirm="Delete {{ $package->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-btn danger" title="Delete"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
