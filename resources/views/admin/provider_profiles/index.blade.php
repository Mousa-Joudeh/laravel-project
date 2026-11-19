@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1>Provider Requests</h1>

        @if (session('success'))
            <p class="alert alert-success">{{ session('success') }}</p>
        @endif

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Company</th>
                        <th scope="col">Submitted By</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($profiles as $profile)
                        <tr>
                            <td>{{ $profile->company_name }}</td>
                            <td>{{ $profile->user?->name ?? '—' }}</td>
                            <td>{{ ucfirst($profile->status) }}</td>
                            <td>
                                <a href="{{ route('provider-profiles.show', $profile) }}">
                                    View Request
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No provider requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $profiles->links('pagination::bootstrap-5') }}
    </div>
@endsection