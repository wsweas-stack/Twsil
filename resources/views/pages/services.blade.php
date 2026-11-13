@extends('layouts.public')

@section('title', __('app.pages.services_title'))

@section('content')
    <section class="page-hero"><div class="page-hero-inner"><span class="hero-kicker">{{ __('app.pages.services_kicker') }}</span><h1>{{ __('app.pages.services_title') }}</h1><p>{{ __('app.pages.services_intro') }}</p></div></section>
    <section class="content-section"><div class="section-inner">
        <div class="service-grid">
            @foreach (__('app.home.services') as $index => $service)
                <article class="service-item"><span class="service-index" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $service['title'] }}</h3><p>{{ $service['description'] }}</p></article>
            @endforeach
        </div>
        @guest
            <p style="margin-top: 28px;"><a class="button-primary" style="width: auto;" href="{{ route('register') }}">{{ __('app.pages.start_shipping') }}</a></p>
        @endguest
    </div></section>
@endsection