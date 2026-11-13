@extends('layouts.public')

@section('title', __('app.pages.about_title'))

@section('content')
    <section class="page-hero"><div class="page-hero-inner"><span class="hero-kicker">{{ __('app.pages.about_kicker') }}</span><h1>{{ __('app.pages.about_title') }}</h1><p>{{ __('app.pages.about_intro') }}</p></div></section>
    <section class="prose-section">
        <h2>{{ __('app.pages.about_section') }}</h2>
        <p>{{ __('app.pages.about_body') }}</p>
        <ul class="feature-list">
            @foreach (__('app.pages.about_points') as $point)
                <li>{{ $point }}</li>
            @endforeach
        </ul>
    </section>
@endsection