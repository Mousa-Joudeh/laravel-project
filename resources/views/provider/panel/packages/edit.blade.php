@extends('layouts.dashboard')

@section('title', 'Edit package')

@section('content')
    <div class="dash-head">
        <div>
            <h1>{{ $package->name }}</h1>
            <p>Update what is inside, the price or the photos.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('provider.packages.update', $package) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('provider.panel.packages._form')
    </form>

    @foreach ($package->images as $image)
        <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('provider.package-images.destroy', $image) }}" data-confirm="Delete this photo?">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endsection
