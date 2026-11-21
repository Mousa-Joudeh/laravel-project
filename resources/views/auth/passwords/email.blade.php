@extends('layouts.user')

@section('title', 'Forgot password')

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

        <section class="auth-panel" aria-labelledby="forgot-password-title">
            <div class="auth-panel-content">
                <h1 id="forgot-password-title">Forgot password?</h1>
                <p class="auth-intro">Enter your email and we’ll send you a link to reset your password.</p>

                @if (session('status'))
                    <p class="auth-intro" role="status">{{ session('status') }}</p>
                @endif

                <form class="auth-form" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               autocomplete="email" required autofocus
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>

                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        Send reset link
                    </button>

                    <a href="{{ route('login') }}" class="btn btn-light btn-lg btn-block">
                        Back to log in
                    </a>
                </form>
            </div>
        </section>
    </div>
@endsection