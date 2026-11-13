@extends('layouts.public')

@section('title', __('app.reviews.public_title'))

@section('content')
    <section class="page-hero"><div class="page-hero-inner"><span class="hero-kicker">{{ __('app.home.reviews_eyebrow') }}</span><h1>{{ __('app.reviews.public_title') }}</h1><p>{{ __('app.reviews.public_intro') }}</p></div></section>
    <section class="prose-section">
        <div class="review-summary"><strong>{{ number_format($averageRating, 1) }} / 5</strong><span>{{ __('app.home.metrics.rating', ['count' => number_format($reviewCount)]) }}</span></div>
        @if ($reviews->isEmpty())
            <p class="empty-state">{{ __('app.home.no_reviews') }}</p>
        @else
            <div class="testimonial-grid">
                @foreach ($reviews as $review)
                    <article class="testimonial"><p>{{ $review->comment ?: __('app.home.rating_only') }}</p><strong>{{ $review->user->name }}</strong><span>{{ __('app.home.rating_label', ['rating' => $review->rating]) }}</span></article>
                @endforeach
            </div>
            <div style="margin-top: 24px;">{{ $reviews->links() }}</div>
        @endif
    </section>
@endsection