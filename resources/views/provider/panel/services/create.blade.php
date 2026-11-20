@extends('layouts.dashboard')

@section('title', 'Add a service')

@section('content')
    <div class="dash-head">
        <div>
            <h1>Add a service</h1>
            <p>It will be visible on SPACES after the admin approves it.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('provider.services.store') }}" enctype="multipart/form-data">
        @csrf
        @include('provider.panel.services._form')
    </form>
@endsection
