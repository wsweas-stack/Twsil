@extends('layouts.public')

@section('title', __('app.auth.login_title'))

@section('content')
    <div class="auth-shell">
        <aside class="auth-aside">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">ت</span>
                <span>{{ __('app.brand.name') }}</span>
            </a>
            <div>
                <p class="eyebrow" style="color: #fff;">{{ __('app.auth.sign_in_eyebrow') }}</p>
                <h1>{{ __('app.auth.sign_in_heading') }}</h1>
                <p>{{ __('app.auth.sign_in_copy') }}</p>
            </div>
            <div class="aside-foot">{{ __('app.brand.aside_note') }}</div>
        </aside>
        <section class="auth-form-panel" aria-labelledby="form-title">
            <div class="auth-form">
                <span class="eyebrow">{{ __('app.auth.welcome_back') }}</span>
                <h2 id="form-title">{{ __('app.auth.login_title') }}</h2>
                <p class="form-intro">{{ __('app.auth.login_intro') }}</p>

                @if (session('status'))
                    <p class="status-message" role="status">{{ session('status') }}</p>
                @endif
                @if ($errors->any())
                    <p class="status-message error" role="alert">{{ __('app.auth.form_error') }}</p>
                @endif

                <form method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="form-field">
                        <label for="email">{{ __('app.auth.email') }}</label>
                        <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus aria-describedby="email-error">
                        @error('email') <p class="field-error" id="email-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="password">{{ __('app.auth.password') }}</label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error">
                        @error('password') <p class="field-error" id="password-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label style="display: flex; align-items: center; gap: 9px; font-weight: 400;">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            {{ __('app.auth.remember') }}
                        </label>
                    </div>
                    <div class="form-actions">
                        <button class="button-primary" type="submit">{{ __('app.auth.login') }}</button>
                    </div>
                </form>
                <p class="form-bottom">{{ __('app.auth.no_account') }} <a class="text-link" href="{{ route('register') }}">{{ __('app.auth.create_account') }}</a></p>
            </div>
        </section>
    </div>
@endsection