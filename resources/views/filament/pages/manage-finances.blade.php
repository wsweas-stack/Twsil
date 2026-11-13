<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <x-filament::section :heading="__('app.finance.delivery_revenue')"><p class="text-xl font-semibold">{{ number_format((float) $totalRevenue, 2) }} {{ config('app.currency', 'SAR') }}</p></x-filament::section>
        <x-filament::section :heading="__('app.finance.driver_commissions')"><p class="text-xl font-semibold">{{ number_format((float) $totalDriverCommissions, 2) }} {{ config('app.currency', 'SAR') }}</p></x-filament::section>
        <x-filament::section :heading="__('app.finance.company_share')"><p class="text-xl font-semibold">{{ number_format((float) $totalCompanyShare, 2) }} {{ config('app.currency', 'SAR') }}</p></x-filament::section>
        <x-filament::section :heading="__('app.finance.paid')"><p class="text-xl font-semibold">{{ number_format((float) $totalPaidCommissions, 2) }} {{ config('app.currency', 'SAR') }}</p></x-filament::section>
        <x-filament::section :heading="__('app.finance.unpaid')"><p class="text-xl font-semibold">{{ number_format((float) $totalUnpaidCommissions, 2) }} {{ config('app.currency', 'SAR') }}</p></x-filament::section>
    </div>

    <x-filament::section :heading="__('app.finance.commissions')">
        <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label class="text-sm font-medium" for="finance-driver-filter">{{ __('app.finance.driver') }}</label>
                <select class="fi-input mt-1 w-full" id="finance-driver-filter" wire:model.live="driverFilter">
                    <option value="">{{ __('app.finance.all_drivers') }}</option>
                    @foreach ($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->name }}</option>@endforeach
                </select>
                @error('driverFilter') <p class="mt-1 text-sm text-danger-600" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium" for="finance-from-date">{{ __('app.finance.from') }}</label>
                <input class="fi-input mt-1 w-full" id="finance-from-date" type="date" wire:model.live="fromDate">
                @error('fromDate') <p class="mt-1 text-sm text-danger-600" role="alert">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium" for="finance-to-date">{{ __('app.finance.to') }}</label>
                <input class="fi-input mt-1 w-full" id="finance-to-date" type="date" wire:model.live="toDate">
                @error('toDate') <p class="mt-1 text-sm text-danger-600" role="alert">{{ $message }}</p> @enderror
            </div>
        </div>
        @if ($commissions->isEmpty())
            <p>{{ __('app.finance.no_commissions') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead><tr><th class="p-3">{{ __('app.finance.date') }}</th><th class="p-3">{{ __('app.finance.shipment') }}</th><th class="p-3">{{ __('app.finance.driver') }}</th><th class="p-3">{{ __('app.finance.driver_commission') }}</th><th class="p-3">{{ __('app.finance.company_share') }}</th><th class="p-3">{{ __('app.finance.status') }}</th><th class="p-3">{{ __('app.finance.action') }}</th></tr></thead>
                    <tbody>
                    @foreach ($commissions as $commission)
                        <tr class="border-t">
                            <td class="p-3">{{ $commission->created_at->format('Y-m-d') }}</td>
                            <td class="p-3">{{ $commission->shipment->tracking_number }}</td>
                            <td class="p-3">{{ $commission->driver->name }}</td>
                            <td class="p-3">{{ number_format((float) $commission->driver_commission, 2) }}</td>
                            <td class="p-3">{{ number_format((float) $commission->company_share, 2) }}</td>
                            <td class="p-3">{{ __('app.finance.statuses.' . $commission->status->value) }}</td>
                            <td class="p-3">
                                @if ($commission->status === \App\Enums\CommissionStatus::Unpaid && auth()->user()->hasRole('admin') && auth()->user()->can('finance.pay_commissions'))
                                    <form wire:submit="markPaid({{ $commission->id }})" class="flex min-w-64 gap-2">
                                        <div class="min-w-0 flex-1">
                                            <input class="fi-input w-full" wire:model="payoutNotes" maxlength="1000" placeholder="{{ __('app.finance.payout_note_placeholder') }}" required aria-describedby="payout-notes-error">
                                            @error('payoutNotes') <p class="mt-1 text-sm text-danger-600" id="payout-notes-error" role="alert">{{ $message }}</p> @enderror
                                        </div>
                                        <button class="fi-btn fi-btn-color-primary" type="submit">{{ __('app.finance.mark_paid') }}</button>
                                    </form>
                                @else
                                    {{ $commission->paid_at?->format('Y-m-d') }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $commissions->links() }}
        @endif
    </x-filament::section>
</x-filament-panels::page>