@extends('layouts.dashboard')

@section('title', 'Edit service')

@section('content')
    <div class="dash-head">
        <div>
            <h1>{{ $service->title }}</h1>
            <p>Update the details, the price or the photo.</p>
        </div>
        <x-status :value="$service->status" />
    </div>

    <form method="POST" action="{{ route('provider.services.update', $service) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('provider.panel.services._form')
    </form>
@endsection
