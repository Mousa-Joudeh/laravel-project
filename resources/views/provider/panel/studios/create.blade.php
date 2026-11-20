@extends('layouts.dashboard')

@section('title', 'Add a space')

@section('content')
    <div class="dash-head">
        <div>
            <h1>Add a space</h1>
            <p>Tell customers what makes this room special. The admin reviews it before it goes live.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('provider.studios.store') }}" enctype="multipart/form-data">
        @csrf
        @include('provider.panel.studios._form')
    </form>
@endsection
