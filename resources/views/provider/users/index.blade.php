@extends('layouts.app')

@section('content')
    <h1>My Customers</h1>

    <ul>
        @forelse ($users as $user)
            <li>{{ $user->name }}</li>
        @empty
            <li>No customers yet.</li>
        @endforelse
    </ul>

    {{ $users->links() }}
@endsection