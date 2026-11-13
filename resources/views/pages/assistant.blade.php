@extends('layouts.public')

@section('title', __('app.assistant.title'))

@section('content')
    <section class="page-hero">
        <div class="page-hero-inner">
            <span class="hero-kicker">{{ __('app.assistant.page_kicker') }}</span>
            <h1>{{ __('app.assistant.title') }}</h1>
            <p>{{ __('app.assistant.intro') }}</p>
        </div>
    </section>
    <section class="ai-assistant-page-content">
        <a class="ai-assistant-back" href="{{ route('contact') }}" data-assistant-back>{{ __('app.assistant.back') }}</a>
        @include('components.ai-assistant', ['fullPage' => true])
    </section>
@endsection
