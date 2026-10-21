<x-layouts.app>
    @php
        $heading = match($mode) { 'register' => 'Create your account', 'forgot' => 'Reset your password', 'reset' => 'Choose a new password', default => 'Welcome back' };
        $action = match($mode) { 'register' => url('/register'), 'forgot' => route('password.email'), 'reset' => route('password.update'), default => url('/login') };
    @endphp
    <section class="auth-card card">
        <p class="eyebrow">YOUR PERSONAL WORKSPACE</p>
        <h1>{{ $heading }}</h1>
        <p class="muted">A quiet place for your thoughts. Private to you.</p>
        @if(session('status')) <p class="notice" role="status">{{ session('status') }}</p> @endif
        @if($errors->any()) <div class="error" role="alert">{{ $errors->first() }}</div> @endif
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($mode === 'reset') <input type="hidden" name="token" value="{{ $token }}"> @endif
            @if($mode === 'register')
                <label for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
            @endif
            <label for="email">Email address</label><input id="email" type="email" name="email" value="{{ old('email', request('email')) }}" required maxlength="255" autocomplete="email">
            @if($mode !== 'forgot')
                <label for="password">Password {{ $mode !== 'login' ? '(at least 12 characters)' : '' }}</label>
                <input id="password" type="password" name="password" required autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" @if($mode !== 'login') minlength="12" @endif>
                @if($mode !== 'login')
                    <label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                @endif
            @endif
            <button class="primary full" type="submit">{{ match($mode) { 'register' => 'Create account', 'forgot' => 'Send reset link', 'reset' => 'Reset password', default => 'Sign in' } }}</button>
        </form>
        <div class="auth-links"><a href="{{ route('login') }}">Sign in</a><a href="{{ route('register') }}">Create account</a><a href="{{ route('password.request') }}">Forgot password?</a></div>
    </section>
</x-layouts.app>
