@extends('layouts.user')

@section('title', 'Reset password')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
    <div class="auth-page">
        <div class="auth-photo" aria-hidden="true">
            <div class="auth-photo-copy">
                <p class="auth-photo-title">Make room for your <span class="serif">next shoot.</span></p>
                <p>Book creative spaces and manage your sessions in one place.</p>
            </div>
        </div>

        <section class="auth-panel" aria-labelledby="reset-password-title">
            <div class="auth-panel-content">
                <h1 id="reset-password-title">Reset password</h1>
                <p class="auth-intro">Choose a new password for your SPACES account.</p>

                <form class="auth-form" method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $email ?? '') }}"
                               autocomplete="email" required
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>

                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password">New password</label>
                        <input id="password" type="password" name="password"
                               autocomplete="new-password" required
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>

                        @error('password')
                            <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password-confirm">Confirm new password</label>
                        <input id="password-confirm" type="password" name="password_confirmation"
                               autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        Reset password
                    </button>

                    <a href="{{ route('login') }}" class="btn btn-light btn-lg btn-block">
                        Back to log in
                    </a>
                </form>
            </div>
        </section>
    </div>
@endsection