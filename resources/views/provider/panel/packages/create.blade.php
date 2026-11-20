@extends('layouts.dashboard')

@section('title', 'New package')

@section('content')
    <div class="dash-head">
        <div>
            <h1>New package</h1>
            <p>Combine your space and services into one simple offer.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('provider.packages.store') }}" enctype="multipart/form-data">
        @csrf
        @include('provider.panel.packages._form')
    </form>
@endsection
