@extends('layouts.public')

@section('title', __('app.auth.account'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.customer_area') }}</span>
            <h1>{{ __('app.auth.hello', ['name' => auth()->user()->name]) }}</h1>
        </header>
        <x-account-nav />
        @if (session('status'))
            <p class="status-message" role="status">{{ session('status') }}</p>
        @endif
        <section class="account-content" aria-labelledby="account-title">
            <h2 id="account-title">{{ __('app.auth.account_ready') }}</h2>
            <p>{{ __('app.auth.account_intro') }}</p>
            <div class="account-actions">
                <a class="button-primary" style="width: auto;" href="{{ route('account.shipments.create') }}">{{ __('app.shipments.create') }}</a>
                <a class="text-link" href="{{ route('account.shipments.index') }}">{{ __('app.shipments.history') }} ({{ number_format($shipmentCount) }})</a>
            </div>
            <h2 class="recent-heading">{{ __('app.shipments.recent') }}</h2>
            @if ($recentShipments->isEmpty())
                <p class="empty-state">{{ __('app.shipments.empty') }}</p>
            @else
                <div class="shipment-list">
                    @foreach ($recentShipments as $shipment)
                        <a class="shipment-row" href="{{ route('account.shipments.show', $shipment) }}">
                            <span><strong>{{ $shipment->tracking_number }}</strong><small>{{ $shipment->receiver_city }}</small></span>
                            <span class="status-pill" data-status="{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection