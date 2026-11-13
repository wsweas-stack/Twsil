<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ReviewService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, AdminSeeder::class]);
    }

    public function test_customer_can_review_owned_delivered_shipment_for_moderation(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->delivered()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->post(route('account.shipments.reviews.store', $shipment), [
                'rating' => 5,
                'comment' => 'Clear delivery updates.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_reviews_require_delivered_owned_shipments(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $other = User::factory()->role(UserRoles::Customer)->create();
        $pendingShipment = Shipment::factory()->status(ShipmentStatus::InTransit)->create(['customer_id' => $customer->id]);
        $otherShipment = Shipment::factory()->delivered()->create(['customer_id' => $other->id]);

        $this->actingAs($customer)
            ->post(route('account.shipments.reviews.store', $pendingShipment), ['rating' => 4])
            ->assertStatus(409);

        $this->post(route('account.shipments.reviews.store', $otherShipment), ['rating' => 4])
            ->assertNotFound();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_rating_must_be_an_integer_from_one_to_five(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->delivered()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->post(route('account.shipments.reviews.store', $shipment), ['rating' => 6])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_one_review_per_shipment_is_enforced(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->delivered()->create(['customer_id' => $customer->id]);
        $service = app(ReviewService::class);

        $service->submit($customer, $shipment, 4, 'First review');

        try {
            $service->submit($customer, $shipment, 5, 'Second review');
            $this->fail('A shipment may only have one review.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_admin_can_moderate_and_hidden_reviews_are_not_public(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->delivered()->create(['customer_id' => $customer->id]);
        $review = Review::query()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Visible after approval.',
            'status' => 'pending',
        ]);
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $service = app(ReviewService::class);

        $service->moderate($admin, $review, 'approved');
        $this->get(route('home'))->assertSee('Visible after approval.');

        $service->moderate($admin, $review, 'hidden');
        $this->get(route('home'))->assertDontSee('Visible after approval.');
    }

    public function test_customer_sees_approved_review_status_in_shipment_history_and_details(): void
    {
        app()->setLocale('ar');

        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->delivered()->create(['customer_id' => $customer->id]);
        Review::factory()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
            'status' => 'approved',
        ]);

        $this->actingAs($customer)
            ->get(route('account.shipments.index'))
            ->assertOk()
            ->assertSee('حالة التقييم')
            ->assertSee('معتمد')
            ->assertSee('review-status-pill', false);

        $this->get(route('account.shipments.show', $shipment))
            ->assertOk()
            ->assertSee('حالة التقييم')
            ->assertSee('معتمد')
            ->assertSee('review-status-panel', false);
    }

    public function test_non_admin_cannot_moderate_reviews(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->delivered()->create(['customer_id' => $customer->id]);
        $review = Review::factory()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
        ]);

        try {
            app(ReviewService::class)->moderate($customer, $review, 'hidden');
            $this->fail('Customers cannot moderate their own reviews.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_admin_review_page_is_restricted_to_admin(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin/manage-reviews')->assertOk();

        $customer = User::factory()->role(UserRoles::Customer)->create();
        $this->actingAs($customer)->get('/admin/manage-reviews')->assertForbidden();
    }
}