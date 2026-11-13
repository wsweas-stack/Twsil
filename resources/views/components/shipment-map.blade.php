@props(['address', 'label' => null])

@php($mapQuery = trim($address))

<section class="shipment-map" aria-label="{{ $label ?? __('app.shipments.map') }}">
    <h2>{{ $label ?? __('app.shipments.map') }}</h2>
    @if (config('services.google_maps.key'))
        <iframe
            title="{{ $label ?? __('app.shipments.map') }}"
            src="https://www.google.com/maps/embed/v1/place?key={{ urlencode(config('services.google_maps.key')) }}&q={{ urlencode($mapQuery) }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
        </iframe>
    @else
        <p>{{ __('app.shipments.map_fallback') }}</p>
        <a class="text-link" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($mapQuery) }}" target="_blank" rel="noopener noreferrer">
            {{ __('app.shipments.open_map') }}
        </a>
    @endif
</section>