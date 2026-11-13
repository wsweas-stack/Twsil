<?php

namespace Tests\Feature;

use App\Enums\UserRoles;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_customer_can_view_and_mark_only_their_own_notifications_read(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $other = User::factory()->role(UserRoles::Customer)->create();
        $customer->notify(new OperationalNotification(
            'shipment_created',
            'app.notifications.shipment_created_subject',
            'app.notifications.shipment_created',
            route('account.dashboard'),
            ['tracking' => 'TW-TEST-1'],
        ));
        $other->notify(new OperationalNotification(
            'shipment_created',
            'app.notifications.shipment_created_subject',
            'app.notifications.shipment_created',
            null,
            ['tracking' => 'TW-PRIVATE-1'],
        ));
        $notification = $customer->notifications()->firstOrFail();
        $otherNotification = $other->notifications()->firstOrFail();

        $this->actingAs($customer)->withSession(['locale' => 'en'])
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertSee('Shipment request received')
            ->assertSee('Your shipment TW-TEST-1 was created and is awaiting a delivery quote.')
            ->assertDontSee('TW-PRIVATE-1');

        $this->post(route('account.notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);

        $this->post(route('account.notifications.read', $otherNotification->id))
            ->assertNotFound();

        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_legacy_arabic_notifications_are_localized_when_viewed_in_english(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $customer->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => OperationalNotification::class,
            'data' => [
                'event' => 'shipment_status_changed',
                'title' => 'تم تحديث حالة الشحنة',
                'message' => 'أصبحت حالة الشحنة TW-OLD123 الآن تم التسليم.',
            ],
        ]);
        $customer->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => OperationalNotification::class,
            'data' => [
                'event' => 'payment_confirmed',
                'title' => 'تم تأكيد الدفع',
                'message' => 'تم تأكيد الدفع للشحنة TW-OLD456.',
            ],
        ]);

        $this->actingAs($customer)->withSession(['locale' => 'en'])
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertSee('Shipment status updated')
            ->assertSee('Shipment TW-OLD123 is now Delivered.')
            ->assertSee('Payment confirmed')
            ->assertSee('Payment for shipment TW-OLD456 was confirmed.');
    }

    public function test_public_registration_creates_a_database_notification(): void
    {
        $this->post('/register', [
            'name' => 'Notification Customer',
            'email' => 'notice@example.test',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => UserRoles::Customer->value,
            'phone' => '+966500000111',
        ])->assertRedirect(route('account.dashboard'));

        $customer = User::query()->where('email', 'notice@example.test')->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => OperationalNotification::class,
        ]);
    }
}