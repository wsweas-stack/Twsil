@extends('layouts.public')

@section('title', __('app.pages.help_title'))

@section('content')
    <section class="page-hero"><div class="page-hero-inner"><span class="hero-kicker">{{ __('app.pages.help_kicker') }}</span><h1>{{ __('app.pages.help_title') }}</h1><p>{{ __('app.pages.help_intro') }}</p></div></section>
    <section class="prose-section">
        <div class="faq-list">
            @foreach (__('app.pages.faqs') as $faq)
                <details><summary>{{ $faq['question'] }}</summary><p>{{ $faq['answer'] }}</p></details>
            @endforeach
        </div>
        <p style="margin-top: 24px;">{{ __('app.pages.need_help') }} <a class="text-link" href="{{ route('contact') }}">{{ __('app.pages.contact_title') }}</a></p>
    </section>
    <a class="button-primary" href="{{ route('assistant.show') }}">
        {{ __('app.assistant.open') }}
    </a>
@endsection