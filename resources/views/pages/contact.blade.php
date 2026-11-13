@extends('layouts.public')

@section('title', __('app.pages.contact_title'))

@section('content')
    <section class="page-hero"><div class="page-hero-inner"><span class="hero-kicker">{{ __('app.pages.contact_kicker') }}</span><h1>{{ __('app.pages.contact_title') }}</h1><p>{{ __('app.pages.contact_intro') }}</p></div></section>
    <section class="prose-section">
        <div class="contact-grid">
            <div><h2>{{ __('app.pages.support_heading') }}</h2><p>{{ __('app.pages.support_copy') }}</p><div class="ai-assistant-contact-actions"><a class="text-link" href="mailto:{{ config('mail.contact.address') }}">{{ config('mail.contact.address') }}</a><a class="ai-assistant-contact-launch" href="{{ route('assistant.show') }}">{{ __('app.assistant.open') }}</a></div></div>
            <form class="contact-form" method="post" action="{{ route('contact.submit') }}">
                @csrf
                <h2>{{ __('app.pages.contact_form_title') }}</h2>
                @if (session('status')) <p class="status-message" role="status">{{ session('status') }}</p> @endif
                @if ($errors->any()) <p class="status-message error" role="alert">{{ __('app.auth.form_error') }}</p> @endif
                <div class="form-field"><label for="name">{{ __('app.auth.full_name') }}</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="email">{{ __('app.auth.email') }}</label><input class="form-control" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="form-field"><label for="message">{{ __('app.pages.message') }}</label><textarea class="form-control" id="message" name="message" minlength="10" maxlength="5000" required>{{ old('message') }}</textarea>@error('message')<p class="field-error">{{ $message }}</p>@enderror</div>
                <button class="button-primary" type="submit">{{ __('app.pages.send_message') }}</button>
            </form>
        </div>
    </section>
@endsection