@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <h1>Become a Provider</h1>

        <form method="POST"
              action="{{ route('provider-profiles.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="company_name" class="form-label">Company Name</label>
                <input id="company_name" name="company_name" type="text"
                       class="form-control" value="{{ old('company_name') }}"
                       maxlength="150" required>

                @error('company_name')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
            </div>

            @foreach ([
                'tagline' => ['Tagline', 160],
                'city' => ['City', 100],
                'instagram' => ['Instagram', 120],
            ] as $field => [$label, $maxLength])
                <div class="mb-3">
                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="text"
                           class="form-control" value="{{ old($field) }}"
                           maxlength="{{ $maxLength }}">

                    @error($field)
                        <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea id="bio" name="bio" class="form-control"
                          rows="4">{{ old('bio') }}</textarea>

                @error('bio')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
            </div>

            @foreach (['logo' => 'Company Logo', 'cover' => 'Cover Image'] as $field => $label)
                <div class="mb-3">
                    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="file"
                           class="form-control"
                           accept="image/jpeg,image/png,image/webp">

                    @error($field)
                        <p class="text-danger">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
    </div>
@endsection