@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <form action="{{ route('register.store') }}" method="post">
        @csrf

        <div>
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')
                <p id="name-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p id="email-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password-hint{{ $errors->has('password') ? ' password-error' : '' }}" @error('password') aria-invalid="true" @enderror>
            <p id="password-hint" class="hint">Use at least 8 characters, up to 72 bytes.</p>
            @error('password')
                <p id="password-error" class="error" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
        </div>

        <button type="submit">Create account</button>
    </form>

    <p class="switch">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
@endsection
