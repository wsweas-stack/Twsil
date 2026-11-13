@extends('layouts.public')

@section('title', __('app.profile.title'))

@section('content')
    <div class="account-shell">
        <header class="account-heading"><span class="eyebrow">{{ __('app.auth.customer_area') }}</span><h1>{{ __('app.profile.title') }}</h1></header>
        <x-account-nav />
        <section class="account-content">
            @if (session('status')) <p class="status-message" role="status">{{ session('status') }}</p> @endif
            @if ($errors->any()) <p class="status-message error" role="alert">{{ __('app.auth.form_error') }}</p> @endif
            <form method="post" action="{{ route('account.profile.update') }}">
                @csrf
                @method('put')
                <div class="form-field"><label for="name">{{ __('app.auth.full_name') }}</label><input class="form-control" id="name" name="name" value="{{ old('name', $profile->name) }}" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="form-grid">
                    <div class="form-field"><label for="email">{{ __('app.auth.email') }}</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $profile->email) }}" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="phone">{{ __('app.auth.phone') }}</label><input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone', $profile->phone) }}" required>@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                </div>
                <fieldset class="shipment-detail form-fieldset">
                    <legend>{{ __('app.profile.password_heading') }}</legend>
                    <p class="field-help">{{ __('app.profile.password_help') }}</p>
                    <div class="form-grid">
                        <div class="form-field"><label for="current_password">{{ __('app.profile.current_password') }}</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password">@error('current_password')<p class="field-error">{{ $message }}</p>@enderror</div>
                        <div class="form-field"><label for="password">{{ __('app.profile.new_password') }}</label><input class="form-control" id="password" name="password" type="password" minlength="10" autocomplete="new-password">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="form-field"><label for="password_confirmation">{{ __('app.auth.confirm_password') }}</label><input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="10" autocomplete="new-password" aria-describedby="password-confirmation-error">@error('password_confirmation')<p class="field-error" id="password-confirmation-error" role="alert">{{ $message }}</p>@enderror</div>
                </fieldset>
                <div class="form-actions"><button class="button-primary" style="width: auto;" type="submit">{{ __('app.profile.save') }}</button></div>
            </form>
        </section>
    </div>
@endsection