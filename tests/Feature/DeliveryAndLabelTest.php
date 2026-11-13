<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\DeliveryOtp;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\DeliveryOtpNotification;
use App\Notifications\OperationalNotification;
use App\Services\DeliveryOtpService;
use App\Services\ShipmentQrCodeService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DeliveryAndLabelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_qr_code_is_unique_encrypted_and_reused_for_a_shipment(): void
    {
        $shipment = Shipment::factory()->create();
        $service = app(ShipmentQrCodeService::class);

        $first = $service->ensureFor($shipment);
        $second = $service->ensureFor($shipment);

        $storedToken = DB::table('shipment_qr_codes')->where('id', $first->id)->value('token');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(hash('sha256', $first->token), $first->token_hash);
        $this->assertNotSame($first->token, $storedToken);
        $this->assertTrue(Storage::disk('local')->exists($first->image_path));
        $this->assertDatabaseCount('shipment_qr_codes', 1);
    }

    public function test_assigned_driver_can_scan_qr_for_pickup_once(): void
    {
        $driver = $this->driver();
        $shipment = Shipment::factory()->status(ShipmentStatus::Assigned)->create(['driver_id' => $driver->id]);
        $qrCode = app(ShipmentQrCodeService::class)->ensureFor($shipment);

        $this->actingAs($driver)
            ->post(route('shipments.scan'), ['token' => $qrCode->token])
            ->assertRedirect('/admin/manage-shipments');

        $this->assertSame(ShipmentStatus::PickedUp, $shipment->fresh()->status);
        $this->actingAs($driver)
            ->post(route('shipments.scan'), ['token' => $qrCode->token])
            ->assertSessionHasErrors('token');
    }

    public function test_driver_cannot_scan_another_drivers_shipment(): void
    {
        $driver = $this->driver();
        $otherDriver = $this->driver();
        $shipment = Shipment::factory()->status(ShipmentStatus::Assigned)->create(['driver_id' => $otherDriver->id]);
        $qrCode = app(ShipmentQrCodeService::class)->ensureFor($shipment);

        $this->actingAs($driver)
            ->post(route('shipments.scan'), ['token' => $qrCode->token])
            ->assertRedirect(route('shipments.scan.form'))
            ->assertSessionHasErrors('token');

        $this->assertSame(ShipmentStatus::Assigned, $shipment->fresh()->status);
    }

    public function test_customer_owner_can_download_label_but_other_customer_cannot(): void
    {
        $owner = User::factory()->role(UserRoles::Customer)->create();
        $other = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create(['customer_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('account.shipments.label', $shipment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($other)
            ->get(route('account.shipments.label', $shipment))
            ->assertNotFound();
    }

    public function test_delivery_code_is_emailed_hashed_and_confirms_delivery_once(): void
    {
        Notification::fake();
        $driver = $this->driver();
        $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create([
            'driver_id' => $driver->id,
            'receiver_email' => 'recipient@example.test',
            'delivery_fee' => '100.00',
        ]);
        $service = app(DeliveryOtpService::class);
        $service->issue($driver, $shipment);

        $otp = DeliveryOtp::query()->where('shipment_id', $shipment->id)->firstOrFail();
        $this->assertSame(0, $otp->attempts);
        $this->assertNull($otp->verified_at);

        $issuedCode = null;
        Notification::assertSentOnDemand(DeliveryOtpNotification::class, function ($notification, array $channels, object $notifiable) use (&$issuedCode): bool {
            $issuedCode = (new \ReflectionProperty($notification, 'code'))->getValue($notification);

            return $notifiable->routes['mail'] === 'recipient@example.test';
        });

        $this->assertIsString($issuedCode);
        $this->assertTrue(Hash::check($issuedCode, $otp->code_hash));

        $delivered = $service->verify($driver, $shipment, $issuedCode);

        $this->assertSame(ShipmentStatus::Delivered, $delivered->fresh()->status);
        $this->assertNotNull($otp->fresh()->verified_at);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'type' => 'delivery_otp_verified',
            'status' => ShipmentStatus::Delivered->value,
        ]);
        $this->assertDatabaseHas('commissions', [
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'driver_commission' => '30.00',
            'company_share' => '70.00',
        ]);
        $this->assertDatabaseCount('wallet_transactions', 1);
        Notification::assertSentTo($shipment->customer, OperationalNotification::class);

        $this->expectException(HttpException::class);
        $service->verify($driver, $shipment, $issuedCode);
    }

    public function test_invalid_delivery_codes_are_limited_and_expired_codes_are_rejected(): void
    {
        Notification::fake();
        $driver = $this->driver();
        $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create([
            'driver_id' => $driver->id,
            'receiver_email' => 'recipient@example.test',
        ]);
        app(DeliveryOtpService::class)->issue($driver, $shipment);
        $otp = DeliveryOtp::query()->where('shipment_id', $shipment->id)->firstOrFail();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                app(DeliveryOtpService::class)->verify($driver, $shipment, '000000');
                $this->fail('Invalid codes should never confirm delivery.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('code', $exception->errors());
            }
        }

        $this->assertSame(3, $otp->fresh()->attempts);
        $this->assertSame(ShipmentStatus::OutForDelivery, $shipment->fresh()->status);

        try {
            app(DeliveryOtpService::class)->verify($driver, $shipment, '000000');
            $this->fail('Attempts must remain blocked after the configured maximum.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
        }

        $this->assertSame(3, $otp->fresh()->attempts);
    }

    public function test_expired_delivery_code_cannot_confirm_delivery(): void
    {
        Notification::fake();
        $driver = $this->driver();
        $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create([
            'driver_id' => $driver->id,
            'receiver_email' => 'recipient@example.test',
        ]);
        app(DeliveryOtpService::class)->issue($driver, $shipment);
        $otp = DeliveryOtp::query()->where('shipment_id', $shipment->id)->firstOrFail();
        $code = null;

        Notification::assertSentOnDemand(DeliveryOtpNotification::class, function ($notification) use (&$code): bool {
            $code = (new \ReflectionProperty($notification, 'code'))->getValue($notification);

            return true;
        });

        $this->assertIsString($code);
        $now = Carbon::now();
        Carbon::setTestNow($now->copy()->addMinutes(11));

        try {
            app(DeliveryOtpService::class)->verify($driver, $shipment, $code);
            $this->fail('Expired codes must not complete delivery.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame(ShipmentStatus::OutForDelivery, $shipment->fresh()->status);
        $this->assertSame(0, $otp->fresh()->attempts);
    }

    private function driver(): User
    {
        return User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
    }
}