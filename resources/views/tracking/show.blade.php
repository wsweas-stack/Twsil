@extends('layouts.public')

@section('title', __('app.tracking.title'))

@section('content')
    <section class="page-hero">
        <div class="page-hero-inner">
            <span class="hero-kicker">{{ __('app.tracking.kicker') }}</span>
            <h1>{{ __('app.tracking.title') }}</h1>
            <p>{{ __('app.tracking.intro') }}</p>
        </div>
    </section>
    <section class="prose-section">
        <form class="track-form" action="{{ route('tracking.lookup') }}" method="post">
            @csrf
            <label class="sr-only" for="tracking_number">{{ __('app.tracking.number') }}</label>
            <input class="form-control" id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}" placeholder="{{ __('app.tracking.placeholder') }}" autocomplete="off" required>
            <button class="button-primary" type="submit">{{ __('app.tracking.submit') }}</button>
        </form>
        @error('tracking_number') <p class="field-error" role="alert">{{ $message }}</p> @enderror

        @if (isset($notFound) && $notFound)
            <p class="status-message error" role="status">{{ __('app.tracking.not_found') }}</p>
        @endif

        @if (isset($shipment) && $shipment)
            <section class="tracking-result" aria-labelledby="tracking-result-title">
                <div class="tracking-header">
                    <div>
                        <span class="eyebrow">{{ __('app.tracking.result_label') }}</span>
                        <h2 id="tracking-result-title" class="tracking-number">{{ $shipment->tracking_number }}</h2>
                    </div>
                    <span class="status-pill" data-status="{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
                </div>
                <ol class="timeline">
                    @forelse ($events as $event)
                        <li>
                            <span class="timeline-dot" aria-hidden="true"></span>
                            <div>
                                <span class="timeline-label">{{ \App\Enums\ShipmentStatus::from($event->status)->label() }}</span>
                                <time class="timeline-meta" datetime="{{ $event->occurred_at->toIso8601String() }}">{{ $event->occurred_at->translatedFormat('M j, Y · g:i A') }}</time>
                            </div>
                        </li>
                    @empty
                        <li>
                            <span class="timeline-dot" aria-hidden="true"></span>
                            <div>
                                <span class="timeline-label">{{ $shipment->status->label() }}</span>
                                <span class="timeline-meta">{{ __('app.tracking.no_events') }}</span>
                            </div>
                        </li>
                    @endforelse
                </ol>
            </section>
        @endif
    </section>
@endsection