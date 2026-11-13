<?php

namespace App\Filament\Pages;

use App\Enums\ApprovalStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentOperationsService;
use App\Services\ShipmentStatusService;
use App\Services\DeliveryOtpService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageShipments extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.manage-shipments';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Shipments';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.admin_nav.shipments');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasInternalAccess()
            && ($user->hasRole(UserRoles::Driver->value) || $user->can('shipments.view_any'));
    }

    public function table(Table $table): Table
    {
        $query = Shipment::query()->with(['customer', 'driver'])->latest();
        $actor = $this->actor();

        if ($actor->hasRole(UserRoles::Driver->value)) {
            $query->where('driver_id', $actor->id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('tracking_number')->label(__('app.tracking.number'))->searchable(),
                TextColumn::make('status')
                    ->label(__('app.shipments.status'))
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label()),
                TextColumn::make('customer.name')->label(__('app.shipments.customer')),
                TextColumn::make('receiver_city')->label(__('app.shipments.city')),
                TextColumn::make('driver.name')->label(__('app.shipments.driver'))->placeholder('—'),
                TextColumn::make('delivery_fee')
                    ->label(__('app.shipments.fee'))
                    ->formatStateUsing(fn (string $state, Shipment $record): string => $state . ' ' . $record->currency),
            ])
            ->recordActions([
                Action::make('open_map')
                    ->label(__('app.shipments.open_in_google_maps'))
                    ->url(fn (Shipment $record): string => $record->receiver_latitude !== null && $record->receiver_longitude !== null
                        ? 'https://www.google.com/maps?q='
                            . urlencode(number_format((float) $record->receiver_latitude, 7, '.', '') . ',' . number_format((float) $record->receiver_longitude, 7, '.', ''))
                        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($record->receiver_address . ', ' . $record->receiver_city))
                    ->openUrlInNewTab(),
                Action::make('quote')
                    ->label(__('app.shipments.quote_fee'))
                    ->visible(fn (Shipment $record): bool => $record->status === ShipmentStatus::Pending && $this->actor()->can('shipments.update'))
                    ->form([
                        TextInput::make('delivery_fee')
                            ->label(__('app.shipments.fee'))
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue(1000000)
                            ->required(),
                    ])
                    ->action(fn (Shipment $record, array $data) => app(ShipmentOperationsService::class)
                        ->quote($this->actor(), $record, (string) $data['delivery_fee'])),
                Action::make('assign_driver')
                    ->label(__('app.shipments.assign_driver'))
                    ->visible(fn (Shipment $record): bool => $record->status === ShipmentStatus::Paid && $this->actor()->can('shipments.assign_driver'))
                    ->form([
                        Select::make('driver_id')
                            ->label(__('app.shipments.driver'))
                            ->options(fn (): array => User::query()
                                ->role(UserRoles::Driver->value)
                                ->where('approval_status', ApprovalStatus::Approved)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Shipment $record, array $data): void {
                        $driver = User::query()->findOrFail($data['driver_id']);
                        app(ShipmentOperationsService::class)->assignDriver($this->actor(), $record, $driver);
                    }),
                Action::make('pickup')
                    ->label(__('app.shipments.confirm_pickup'))
                    ->icon(Heroicon::OutlinedQrCode)
                    ->requiresConfirmation()
                    ->visible(fn (Shipment $record): bool => $this->actor()->hasRole(UserRoles::Driver->value)
                        && $record->driver_id === $this->actor()->id
                        && $record->status === ShipmentStatus::Assigned)
                    ->action(fn (Shipment $record) => app(ShipmentStatusService::class)
                        ->transition($this->actor(), $record, ShipmentStatus::PickedUp)),
                Action::make('start_transit')
                    ->label(__('app.shipments.start_transit'))
                    ->visible(fn (Shipment $record): bool => $this->actor()->hasRole(UserRoles::Driver->value)
                        && $record->driver_id === $this->actor()->id
                        && $record->status === ShipmentStatus::PickedUp)
                    ->action(fn (Shipment $record) => app(ShipmentStatusService::class)
                        ->transition($this->actor(), $record, ShipmentStatus::InTransit)),
                Action::make('out_for_delivery')
                    ->label(__('app.shipments.out_for_delivery'))
                    ->visible(fn (Shipment $record): bool => $this->actor()->hasRole(UserRoles::Driver->value)
                        && $record->driver_id === $this->actor()->id
                        && $record->status === ShipmentStatus::InTransit)
                    ->action(fn (Shipment $record) => app(ShipmentStatusService::class)
                        ->transition($this->actor(), $record, ShipmentStatus::OutForDelivery)),
                Action::make('send_delivery_code')
                    ->label(__('app.shipments.send_delivery_code'))
                    ->visible(fn (Shipment $record): bool => $this->actor()->hasRole(UserRoles::Driver->value)
                        && $record->driver_id === $this->actor()->id
                        && $record->status === ShipmentStatus::OutForDelivery)
                    ->requiresConfirmation()
                    ->action(fn (Shipment $record) => app(DeliveryOtpService::class)->issue($this->actor(), $record)),
                Action::make('verify_delivery_code')
                    ->label(__('app.shipments.verify_delivery_code'))
                    ->visible(fn (Shipment $record): bool => $this->actor()->hasRole(UserRoles::Driver->value)
                        && $record->driver_id === $this->actor()->id
                        && $record->status === ShipmentStatus::OutForDelivery)
                    ->form([
                        TextInput::make('code')
                            ->label(__('app.shipments.delivery_code'))
                            ->numeric()
                            ->length(6)
                            ->required(),
                    ])
                    ->action(fn (Shipment $record, array $data) => app(DeliveryOtpService::class)
                        ->verify($this->actor(), $record, $data['code'])),
            ])
            ->emptyStateHeading(__('app.shipments.no_records'))
            ->emptyStateDescription(__('app.shipments.no_records_description'));
    }

    private function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}