@extends('layouts.public')

@section('title', __('app.shipments.details'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.customer_area') }}</span>
            <h1>{{ __('app.shipments.details') }}</h1>
        </header>
        <x-account-nav />
        @if (session('status')) <p class="status-message" role="status">{{ session('status') }}</p> @endif
        <div class="shipment-toolbar">
            <span class="tracking-number">{{ $shipment->tracking_number }}</span>
            <span class="status-pill" data-status="{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
        </div>
        <div class="shipment-toolbar">
            <a class="text-link" href="{{ route('account.shipments.index') }}">{{ __('app.shipments.history') }}</a>
            @if ($shipment->invoice_path)
                <a class="text-link" href="{{ route('account.shipments.invoice', $shipment) }}">{{ __('app.shipments.download_invoice') }}</a>
            @endif
            <a class="text-link" href="{{ route('account.shipments.label', $shipment) }}">{{ __('app.labels.download') }}</a>
            @if ($shipment->status === \App\Enums\ShipmentStatus::AwaitingPayment)
                <form method="post" action="{{ route('payments.checkout', $shipment) }}">
                    @csrf
                    <button class="button-primary" style="width: auto;" type="submit">{{ __('app.payments.pay_now') }}</button>
                </form>
            @endif
            @if (in_array($shipment->status, [\App\Enums\ShipmentStatus::Pending, \App\Enums\ShipmentStatus::AwaitingPayment], true))
                <form method="post" action="{{ route('account.shipments.cancel', $shipment) }}" data-confirm="{{ __('app.shipments.cancel_confirm') }}">
                    @csrf
                    <button class="text-link" type="submit" style="border: 0; background: transparent; cursor: pointer;">{{ __('app.shipments.cancel') }}</button>
                </form>
            @endif
        </div>
        <div class="shipment-detail-grid">
            <section class="shipment-detail"><h2>{{ __('app.shipments.sender') }}</h2><p><strong>{{ $shipment->sender_name }}</strong></p><p>{{ $shipment->sender_phone }}</p><p>{{ $shipment->sender_city }}</p><p>{{ $shipment->sender_address }}</p></section>
            <section class="shipment-detail"><h2>{{ __('app.shipments.receiver') }}</h2><p><strong>{{ $shipment->receiver_name }}</strong></p><p>{{ $shipment->receiver_phone }}</p><p>{{ $shipment->receiver_city }}</p><p>{{ $shipment->receiver_address }}</p>
                @if ($shipment->receiver_latitude !== null && $shipment->receiver_longitude !== null)
                    <p class="location-saved"><strong>{{ __('app.shipments.location_saved') }}:</strong><span class="location-saved-coordinates">{{ number_format((float) $shipment->receiver_latitude, 7, '.', '') }}, {{ number_format((float) $shipment->receiver_longitude, 7, '.', '') }}</span></p>
                    <a class="text-link" href="https://www.google.com/maps?q={{ $shipment->receiver_latitude }},{{ $shipment->receiver_longitude }}" target="_blank" rel="noopener noreferrer">{{ __('app.shipments.open_in_google_maps') }}</a>
                @else
                    <p class="location-none">{{ __('app.shipments.location_none') }}</p>
                @endif
                <x-location-picker :shipment="$shipment" :validation-errors="$errors" />
            </section>
            <section class="shipment-detail"><h2>{{ __('app.shipments.parcel') }}</h2><p>{{ $shipment->description }}</p><p>{{ __('app.shipments.weight') }}: {{ $shipment->weight_kg ?: __('app.common.not_provided') }}</p><p>{{ __('app.shipments.fee') }}: {{ number_format((float) $shipment->delivery_fee, 2) }} {{ $shipment->currency }}</p></section>
            <section class="shipment-detail"><h2>{{ __('app.shipments.timeline') }}</h2><ol class="timeline">
                @forelse ($shipment->events as $event)
                    <li><span class="timeline-dot" aria-hidden="true"></span><div><span class="timeline-label">{{ \App\Enums\ShipmentStatus::tryFrom($event->status)?->label() ?? $event->status }}</span><time class="timeline-meta" datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->translatedFormat('M j, Y · g:i A') }}</time></div></li>
                @empty
                    <li><span class="timeline-dot" aria-hidden="true"></span><div><span class="timeline-label">{{ $shipment->status->label() }}</span></div></li>
                @endforelse
            </ol></section>
        </div>
        <x-shipment-map :address="$shipment->receiver_address . ', ' . $shipment->receiver_city" />
        @if ($shipment->invoice_extracted_text)
            <section class="account-content" style="margin-top: 22px;"><h2>{{ __('app.shipments.extracted_invoice') }}</h2><p class="field-help">{{ __('app.shipments.extracted_invoice_help') }}</p><pre class="invoice-text">{{ $shipment->invoice_extracted_text }}</pre></section>
        @elseif ($shipment->invoice_path)
            <p class="status-message">{{ __('app.shipments.extraction_unavailable') }}</p>
        @endif
        @if ($shipment->status === \App\Enums\ShipmentStatus::Delivered && ! $shipment->review)
            <section class="account-content" style="margin-top: 22px;">
                <h2>{{ __('app.reviews.leave_review') }}</h2>
                <form method="post" action="{{ route('account.shipments.reviews.store', $shipment) }}">
                    @csrf
                    <div class="form-field">
                        <label for="rating">{{ __('app.reviews.rating') }}</label>
                        <select class="form-control" id="rating" name="rating" required>
                            <option value="">{{ __('app.reviews.select_rating') }}</option>
                            @for ($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}" @selected((string) old('rating') === (string) $rating)>{{ $rating }} / 5</option>
                            @endfor
                        </select>
                        @error('rating') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field"><label for="review-comment">{{ __('app.reviews.comment') }}</label><textarea class="form-control" id="review-comment" name="comment" maxlength="2000">{{ old('comment') }}</textarea>@error('comment')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <button class="button-primary" style="width: auto;" type="submit">{{ __('app.reviews.submit') }}</button>
                </form>
            </section>
        @elseif ($shipment->review)
            <section class="review-status-panel" aria-label="{{ __('app.reviews.review_status_label') }}">
                <strong>{{ __('app.reviews.review_status_label') }}</strong>
                <span class="review-status-pill" data-status="{{ $shipment->review->status }}">{{ __('app.reviews.statuses.' . $shipment->review->status) }}</span>
            </section>
        @endif
    </div>
@endsection