<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Commission;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_rates_split_delivery_fee_in_cents_and_conserve_total(): void
    {
        $shipment = $this->deliveredShipment('19.99');

        $commission = app(CommissionService::class)->recordForDeliveredShipment($shipment);

        $this->assertSame('6.00', $commission->driver_commission);
        $this->assertSame('13.99', $commission->company_share);
        $this->assertSame('19.99', $commission->delivery_fee);
        $this->assertSame('30.00', $commission->driver_commission_rate);
        $this->assertSame(CommissionStatus::Unpaid, $commission->status);
        $this->assertSame('6.00', WalletTransaction::query()->where('commission_id', $commission->id)->firstOrFail()->amount);
    }

    public function test_configured_rates_are_used_and_driver_credit_is_created_once(): void
    {
        Setting::set(Setting::DRIVER_COMMISSION_RATE, '25');
        Setting::set(Setting::COMPANY_SHARE_RATE, '75');
        $shipment = $this->deliveredShipment('10.00');
        $service = app(CommissionService::class);

        $first = $service->recordForDeliveredShipment($shipment);
        $second = $service->recordForDeliveredShipment($shipment);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('2.50', $first->driver_commission);
        $this->assertSame('7.50', $first->company_share);
        $this->assertSame(1, Commission::query()->where('shipment_id', $shipment->id)->count());
        $this->assertSame(1, WalletTransaction::query()->where('commission_id', $first->id)->count());
    }

    public function test_non_delivered_or_unassigned_shipments_cannot_earn_commission(): void
    {
        $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create();

        try {
            app(CommissionService::class)->recordForDeliveredShipment($shipment);
            $this->fail('A shipment must be delivered and assigned before commission is recorded.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('commissions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_invalid_configured_percentages_do_not_create_commission(): void
    {
        Setting::set(Setting::DRIVER_COMMISSION_RATE, '40');
        Setting::set(Setting::COMPANY_SHARE_RATE, '50');
        $shipment = $this->deliveredShipment('10.00');

        try {
            app(CommissionService::class)->recordForDeliveredShipment($shipment);
            $this->fail('Configured commission percentages must total 100%.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('commission_rate', $exception->errors());
        }

        $this->assertDatabaseCount('commissions', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    private function deliveredShipment(string $fee): Shipment
    {
        $driver = User::factory()->role(UserRoles::Driver)->create();

        return Shipment::factory()->delivered()->create([
            'driver_id' => $driver->id,
            'delivery_fee' => $fee,
        ]);
    }
}