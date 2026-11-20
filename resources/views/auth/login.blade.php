@extends('layouts.user')

@section('title', 'Log in')

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

        <section class="auth-panel" aria-labelledby="login-title">
            <div class="auth-panel-content">
                <h1 id="login-title">Log in</h1>
                <p class="auth-intro">
                    New to SPACES? <a href="{{ route('register') }}">Create an account</a>
                </p>

                <form class="auth-form" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               autocomplete="email" required
                               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        @error('email')
                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password"
                               autocomplete="current-password" required
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        @error('password')
                            <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="auth-options">
                        <label class="auth-remember" for="remember">
                            <input id="remember" type="checkbox" name="remember" @checked(old('remember'))>
                            Keep me logged in
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">Log in</button>

                    <a href="{{ route('auth.google.redirect') }}" class="btn btn-light btn-lg btn-block">
                        <i class="bi bi-google" aria-hidden="true"></i>
                        Continue with Google
                    </a>
                </form>
            </div>
        </section>
    </div>
@endsection