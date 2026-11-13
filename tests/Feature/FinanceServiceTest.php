<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Enums\WalletTransactionType;
use App\Models\Commission;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\CommissionService;
use App\Services\FinanceService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FinanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, AdminSeeder::class]);
        $this->admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
    }

    public function test_admin_records_a_single_commission_payout_and_wallet_transaction(): void
    {
        $commission = $this->commission();
        $service = app(FinanceService::class);

        $paid = $service->markCommissionPaid($this->admin, $commission, 'Bank transfer reference 1001');

        $this->assertSame(CommissionStatus::Paid, $paid->status);
        $this->assertNotNull($paid->paid_at);
        $this->assertSame('Bank transfer reference 1001', $paid->payment_notes);
        $this->assertDatabaseHas('wallet_transactions', [
            'driver_id' => $commission->driver_id,
            'type' => WalletTransactionType::Payout->value,
            'amount' => '-30.00',
        ]);

        try {
            $service->markCommissionPaid($this->admin, $commission, 'Duplicate payout');
            $this->fail('A paid commission must not be paid a second time.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertSame(2, WalletTransaction::query()->count());
    }

    public function test_admin_cannot_record_a_payout_without_notes(): void
    {
        $commission = $this->commission();

        try {
            app(FinanceService::class)->markCommissionPaid($this->admin, $commission, '');
            $this->fail('A payout note is required.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('notes', $exception->errors());
        }

        $this->assertSame(CommissionStatus::Unpaid, $commission->fresh()->status);
    }

    public function test_only_admin_with_finance_permission_can_record_payout(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create();
        $commission = $this->commission();

        try {
            app(FinanceService::class)->markCommissionPaid($employee, $commission, 'Unauthorized');
            $this->fail('Only an Admin with payout permission may mark commissions paid.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame(CommissionStatus::Unpaid, $commission->fresh()->status);
    }

    public function test_admin_can_configure_rates_that_total_100_percent(): void
    {
        app(FinanceService::class)->configureRates($this->admin, '25', '75');

        $this->assertSame('25.00', Setting::get(Setting::DRIVER_COMMISSION_RATE));
        $this->assertSame('75.00', Setting::get(Setting::COMPANY_SHARE_RATE));
    }

    public function test_incorrect_rate_totals_are_rejected_without_persisting(): void
    {
        try {
            app(FinanceService::class)->configureRates($this->admin, '35', '70');
            $this->fail('Commission rates must total exactly 100%.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('driver_commission_rate', $exception->errors());
        }

        $this->assertSame('30', Setting::get(Setting::DRIVER_COMMISSION_RATE));
        $this->assertSame('70', Setting::get(Setting::COMPANY_SHARE_RATE));
    }

    public function test_admin_can_open_the_finance_page(): void
    {
        $this->commission();

        $this->actingAs($this->admin)
            ->get('/admin/manage-finances')
            ->assertOk()
            ->assertSee(__('app.finance.unpaid'));
    }

    public function test_driver_can_open_their_wallet_page(): void
    {
        $commission = $this->commission();
        $driver = $commission->driver;

        $this->actingAs($driver)
            ->get('/admin/driver-wallet')
            ->assertOk()
            ->assertSee('30.00');
    }

    public function test_customer_cannot_open_financial_pages(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();

        $this->actingAs($customer)->get('/admin/driver-wallet')->assertForbidden();
        $this->actingAs($customer)->get('/admin/manage-finances')->assertForbidden();
    }

    public function test_employee_with_finance_view_permission_can_view_reports_without_payout_controls(): void
    {
        $commission = $this->commission();
        $employee = User::factory()->role(UserRoles::Employee)->create([
            'approval_status' => \App\Enums\ApprovalStatus::Approved,
        ]);
        $employee->givePermissionTo('finance.view_reports');

        $this->actingAs($employee)
            ->get('/admin/manage-finances')
            ->assertOk()
            ->assertSee($commission->shipment->tracking_number)
            ->assertDontSee(__('app.finance.mark_paid'));
    }

    private function commission(): Commission
    {
        $driver = User::factory()->role(UserRoles::Driver)->create();
        $shipment = Shipment::factory()->delivered()->create([
            'driver_id' => $driver->id,
            'delivery_fee' => '100.00',
        ]);

        return app(CommissionService::class)->recordForDeliveredShipment($shipment);
    }
}