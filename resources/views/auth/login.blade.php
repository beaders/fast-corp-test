@extends('layouts.auth')

@section('title', 'Log in')

@section('content')
    <form action="{{ route('login.store') }}" method="post">
        @csrf

        <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <p id="password-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit">Log in</button>
    </form>

    <p class="switch">Don't have an account? <a href="{{ route('register') }}">Register</a></p>
@endsection
