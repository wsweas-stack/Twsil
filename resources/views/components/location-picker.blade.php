@props(['shipment', 'validationErrors'])

@php($mapsKey = config('services.google_maps.key'))
@php($editable = ! in_array($shipment->status, [\App\Enums\ShipmentStatus::Delivered, \App\Enums\ShipmentStatus::Cancelled], true))
@php($hasSavedLocation = $shipment->receiver_latitude !== null && $shipment->receiver_longitude !== null)
@php($hasLocationErrors = $validationErrors->has('latitude') || $validationErrors->has('longitude'))

@if ($editable && $mapsKey)
    <button class="button-primary" style="width: auto;" type="button" data-location-picker-open>{{ __('app.shipments.pick_location') }}</button>
@endif

@if ($editable && $mapsKey)
    <dialog id="location-picker-dialog" class="location-dialog" aria-labelledby="location-picker-title"@if ($hasLocationErrors || old('latitude') !== null || old('longitude') !== null) open @endif>
        <div class="location-dialog-body">
            <header class="location-dialog-header">
                <h2 id="location-picker-title">{{ __('app.shipments.picker_title') }}</h2>
                <button class="location-dialog-close" type="button" data-location-picker-close aria-label="{{ __('app.common.close') }}">&times;</button>
            </header>
            <p class="field-help" style="margin: 0;">{{ __('app.shipments.picker_help') }}</p>
            <div
                id="location-picker-map"
                class="location-picker-map"
                data-maps-key="{{ $mapsKey }}"
                data-locale="{{ app()->getLocale() }}"
                data-initial-lat="{{ $hasSavedLocation ? $shipment->receiver_latitude : '' }}"
                data-initial-lng="{{ $hasSavedLocation ? $shipment->receiver_longitude : '' }}"
            ></div>
            <p id="location-picker-error" class="location-picker-error" hidden>{{ __('app.shipments.picker_error') }}</p>
            <p id="location-picker-unsaved" class="location-picker-unsaved" hidden>{{ __('app.shipments.location_unsaved') }}</p>
            <p id="location-picker-coordinates" class="location-picker-coordinates" aria-live="polite"></p>
            <form method="post" action="{{ route('account.shipments.location', $shipment) }}" class="location-picker-actions">
                @csrf
                <input id="location-latitude" name="latitude" type="hidden">
                <input id="location-longitude" name="longitude" type="hidden">
                <button id="location-picker-confirm" class="button-primary" type="submit" disabled>{{ __('app.shipments.confirm_location') }}</button>
            </form>
            @if ($validationErrors->has('latitude')) <p class="field-error" style="margin: 0;">{{ $validationErrors->first('latitude') }}</p> @endif
            @if ($validationErrors->has('longitude')) <p class="field-error" style="margin: 0;">{{ $validationErrors->first('longitude') }}</p> @endif
        </div>
    </dialog>
@elseif ($editable)
    <p class="field-help" role="status">{{ __('app.shipments.picker_unavailable') }}</p>
@endif
