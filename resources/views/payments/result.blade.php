@extends('layouts.public')

@section('title', __('app.payments.result_title'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.payments.eyebrow') }}</span>
            <h1>{{ __('app.payments.result_title') }}</h1>
        </header>
        <x-account-nav />
        <section class="account-content" aria-live="polite">
            @if (session('payment_status') === \App\Enums\PaymentStatus::Succeeded->value
                || $payment?->status === \App\Enums\PaymentStatus::Succeeded)
                <h2>{{ __('app.payments.confirmed') }}</h2>
                <p>{{ $payment?->shipment?->tracking_number }}</p>
                <a class="button-primary" style="width: auto;" href="{{ $payment ? route('account.shipments.show', $payment->shipment) : route('account.shipments.index') }}">
                    {{ __('app.payments.view_shipment') }}
                </a>
            @elseif (! $payment)
                <h2>{{ __('app.payments.not_found') }}</h2>
                <p>{{ __('app.payments.not_found_copy') }}</p>
            @elseif (session('payment_status') === \App\Enums\PaymentStatus::Cancelled->value
                || $payment->status === \App\Enums\PaymentStatus::Cancelled)
                <h2>{{ __('app.payments.cancelled') }}</h2>
                <p>{{ __('app.payments.cancelled_copy') }}</p>
                <a class="button-secondary" style="width: auto;" href="{{ route('account.shipments.show', $payment->shipment) }}">
                    {{ __('app.payments.back_to_shipment') }}
                </a>
            @else
                <h2>{{ $cancelled ? __('app.payments.checkout_returned') : __('app.payments.processing') }}</h2>
                <p>{{ __('app.payments.processing_copy') }}</p>
                <p class="tracking-number">{{ $payment->shipment->tracking_number }}</p>
                @if (session('payment_status') === \App\Enums\PaymentStatus::Pending->value)
                    <p class="status-message error">{{ __('app.payments.not_paid_yet') }}</p>
                @endif
                <div class="payment-result-actions">
                    <form method="post" action="{{ route('payments.confirm', $payment) }}">
                        @csrf
                        <button class="button-primary" type="submit">{{ __('app.payments.confirm_button') }}</button>
                    </form>
                    <form
                        method="post"
                        action="{{ route('payments.cancel', $payment) }}"
                        data-confirm="{{ __('app.payments.cancel_confirm') }}"
                    >
                        @csrf
                        <button class="button-secondary" type="submit">{{ __('app.payments.cancel_button') }}</button>
                    </form>
                    <a class="text-link payment-back-link" href="{{ route('account.shipments.show', $payment->shipment) }}">
                        {{ __('app.payments.back_to_shipment') }}
                    </a>
                </div>
            @endif
            <a class="text-link" href="{{ route('account.shipments.index') }}">{{ __('app.shipments.history') }}</a>
        </section>
    </div>
@endsection