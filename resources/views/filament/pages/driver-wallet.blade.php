<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-filament::section :heading="__('app.finance.total_earned')">
            <p class="text-2xl font-semibold">{{ number_format((float) $earned, 2) }} {{ config('app.currency', 'SAR') }}</p>
        </x-filament::section>
        <x-filament::section :heading="__('app.finance.paid')">
            <p class="text-2xl font-semibold">{{ number_format((float) $paid, 2) }} {{ config('app.currency', 'SAR') }}</p>
        </x-filament::section>
        <x-filament::section :heading="__('app.finance.unpaid')">
            <p class="text-2xl font-semibold">{{ number_format((float) $unpaid, 2) }} {{ config('app.currency', 'SAR') }}</p>
        </x-filament::section>
    </div>

    <x-filament::section :heading="__('app.finance.wallet_history')">
        @if ($transactions->isEmpty())
            <p>{{ __('app.finance.no_transactions') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-start text-sm">
                    <thead><tr><th class="p-3">{{ __('app.finance.date') }}</th><th class="p-3">{{ __('app.finance.type') }}</th><th class="p-3">{{ __('app.finance.description') }}</th><th class="p-3">{{ __('app.finance.amount') }}</th></tr></thead>
                    <tbody>
                    @foreach ($transactions as $transaction)
                        <tr class="border-t">
                            <td class="p-3">{{ $transaction->created_at->format('Y-m-d') }}</td>
                            <td class="p-3">{{ __('app.finance.transaction_types.' . $transaction->type->value) }}</td>
                            <td class="p-3">{{ $transaction->description }}</td>
                            <td class="p-3">{{ number_format((float) $transaction->amount, 2) }} {{ config('app.currency', 'SAR') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        @endif
    </x-filament::section>
</x-filament-panels::page>