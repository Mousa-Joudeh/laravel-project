@extends('layouts.dashboard')

@section('title', 'Edit space')

@section('content')
    <div class="dash-head">
        <div>
            <h1>{{ $studio->name }}</h1>
            <p>Update the details, equipment and photos of this space.</p>
        </div>
        <x-status :value="$studio->status" />
    </div>

    <form method="POST" action="{{ route('provider.studios.update', $studio) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('provider.panel.studios._form')
    </form>

    {{-- small forms used by the photo buttons above (forms cannot be nested) --}}
    @foreach ($studio->images as $image)
        <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('provider.studio-images.destroy', $image) }}" data-confirm="Delete this photo?">
            @csrf
            @method('DELETE')
        </form>
        <form id="cover-{{ $image->id }}" method="POST" action="{{ route('provider.studio-images.cover', $image) }}">
            @csrf
            @method('PATCH')
        </form>
    @endforeach
@endsection
