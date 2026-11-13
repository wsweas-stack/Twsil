@extends('layouts.public')

@section('title', __('app.shipments.history'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.customer_area') }}</span>
            <h1>{{ __('app.shipments.history') }}</h1>
        </header>
        <x-account-nav />
        <div class="account-actions">
            <a class="button-primary" style="width: auto;" href="{{ route('account.shipments.create') }}">{{ __('app.shipments.create') }}</a>
            <a class="text-link" href="{{ route('account.dashboard') }}">{{ __('app.auth.account') }}</a>
        </div>
        @if ($shipments->isEmpty())
            <section class="account-content"><p class="empty-state">{{ __('app.shipments.empty') }}</p></section>
        @else
            <section class="account-content shipment-list" aria-label="{{ __('app.shipments.history') }}">
                @foreach ($shipments as $shipment)
                    <a class="shipment-row" href="{{ route('account.shipments.show', $shipment) }}">
                        <span class="shipment-row-details">
                            <strong>{{ $shipment->tracking_number }}</strong>
                            <small>{{ $shipment->receiver_city }} · {{ $shipment->created_at->format('M j, Y') }}</small>
                            @if ($shipment->review)
                                <small class="shipment-review-status">
                                    <span>{{ __('app.reviews.review_status_label') }}:</span>
                                    <span class="review-status-pill" data-status="{{ $shipment->review->status }}">{{ __('app.reviews.statuses.' . $shipment->review->status) }}</span>
                                </small>
                            @endif
                        </span>
                        <span class="status-pill" data-status="{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
                    </a>
                @endforeach
                <div style="margin-top: 20px;">{{ $shipments->links() }}</div>
            </section>
        @endif
    </div>
@endsection