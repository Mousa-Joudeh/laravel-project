@extends('layouts.app')

@section('content')
    <div class="container py-4">
        @if (session('success'))
            <p class="alert alert-success">{{ session('success') }}</p>
        @endif

        @if ($profile->cover)
            <img src="{{ asset('storage/' . $profile->cover) }}"
                 alt="{{ $profile->company_name }} cover"
                 class="img-fluid rounded mb-3">
        @endif

        @if ($profile->logo)
            <img src="{{ asset('storage/' . $profile->logo) }}"
                 alt="{{ $profile->company_name }} logo"
                 width="96"
                 class="img-fluid rounded mb-3">
        @endif

        <h1>{{ $profile->company_name }}</h1>

        @if ($profile->tagline)
            <p class="text-muted">{{ $profile->tagline }}</p>
        @endif

        @if (auth()->user()->role === 'admin')
            <p>Submitted by: {{ $profile->user?->name ?? '—' }}</p>
        @endif

        <p>Status: {{ ucfirst($profile->status) }}</p>

        @if ($profile->city)
            <p>City: {{ $profile->city }}</p>
        @endif

        @if ($profile->instagram)
            <p>Instagram: {{ $profile->instagram }}</p>
        @endif

        @if ($profile->bio)
            <h2 class="h5">About the Company</h2>
            <p>{{ $profile->bio }}</p>
        @endif

        @if ($profile->admin_note)
            <p>Review note: {{ $profile->admin_note }}</p>
        @endif

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('provider-profiles.edit', $profile) }}"
               class="btn btn-outline-primary">
                Edit Profile
            </a>

            <form method="POST"
                  action="{{ route('provider-profiles.destroy', $profile) }}"
                  onsubmit="return confirm('Delete this provider profile?')">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-outline-danger">
                    Delete Profile
                </button>
            </form>
        </div>

        @if (auth()->user()->role === 'admin' && $profile->status === 'pending')
            <form method="POST" action="{{ route('admin.provider-profiles.review', $profile) }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="admin_note" class="form-label">Review Note</label>
                    <textarea id="admin_note" name="admin_note" class="form-control"
                              maxlength="255">{{ old('admin_note') }}</textarea>

                    @error('admin_note')
                        <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>

                @error('status')
                    <p class="text-danger">{{ $message }}</p>
                @enderror

                <button type="submit" name="status" value="approved" class="btn btn-success">
                    Approve
                </button>

                <button type="submit" name="status" value="rejected" class="btn btn-danger">
                    Reject
                </button>
            </form>
        @endif
    </div>
@endsection