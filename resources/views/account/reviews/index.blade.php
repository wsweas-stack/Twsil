@extends('layouts.public')

@section('title', __('app.reviews.my_reviews'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.customer_area') }}</span>
            <h1>{{ __('app.reviews.my_reviews') }}</h1>
        </header>
        <x-account-nav />
        @if ($reviews->isEmpty())
            <section class="account-content"><p class="empty-state">{{ __('app.reviews.customer_empty') }}</p></section>
        @else
            <section class="account-content shipment-list">
                @foreach ($reviews as $review)
                    <article class="shipment-row">
                        <span><strong>{{ $review->shipment->tracking_number }}</strong><small>{{ $review->comment ?: __('app.reviews.rating_only') }}</small></span>
                        <span><strong>{{ $review->rating }} / 5</strong><small>{{ __('app.reviews.statuses.' . $review->status) }}</small></span>
                    </article>
                @endforeach
                {{ $reviews->links() }}
            </section>
        @endif
    </div>
@endsection