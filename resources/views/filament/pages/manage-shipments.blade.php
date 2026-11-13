<x-filament-panels::page>
    @if (auth()->user()?->can('shipments.pickup'))
        <x-filament::button tag="a" :href="route('shipments.scan.form')" icon="heroicon-o-qr-code">
            {{ __('app.shipments.scan_title') }}
        </x-filament::button>
    @endif
    {{ $this->table }}
</x-filament-panels::page>