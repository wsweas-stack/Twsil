<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Mail\ContactMessageMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_real_shipment_counts_and_only_approved_reviews(): void
    {
        $shipment = Shipment::factory()->delivered()->create(['receiver_city' => 'Riyadh']);
        $hiddenShipment = Shipment::factory()->delivered()->create(['receiver_city' => 'Jeddah']);

        Review::query()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $shipment->customer_id,
            'rating' => 5,
            'comment' => 'A verified customer review.',
            'status' => 'approved',
        ]);
        Review::query()->create([
            'shipment_id' => $hiddenShipment->id,
            'user_id' => $hiddenShipment->customer_id,
            'rating' => 1,
            'comment' => 'A review awaiting approval.',
            'status' => 'hidden',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('shipmentCount', 2)
            ->assertViewHas('deliveredCount', 2)
            ->assertViewHas('cityCount', 2)
            ->assertViewHas('reviewCount', 1)
            ->assertSee(app()->getLocale() === 'ar' ? 'إجمالي الشحنات المدارة' : 'Total Shipments')
            ->assertSee(app()->getLocale() === 'ar' ? 'شحنة مُسلَّمة بنجاح' : 'Delivered Shipments')
            ->assertSee(app()->getLocale() === 'ar' ? 'مدينة ومحافظة مغطاة' : 'Cities Covered')
            ->assertSee('A verified customer review.')
            ->assertDontSee('A review awaiting approval.');
    }

    public function test_homepage_process_section_renders_four_ordered_steps_with_decorative_icons(): void
    {
        $response = $this->get(route('home'))->assertOk();
        $content = $response->getContent();

        $this->assertSame(4, substr_count($content, 'class="step-card"'));
        $this->assertSame(4, substr_count($content, 'class="step-badge"'));
        $this->assertSame(4, substr_count($content, 'class="step-icon-circle"'));
    }

    public function test_homepage_does_not_show_placeholder_ratings_or_testimonials(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('app.home.no_reviews'))
            ->assertDontSee('4.9 / 5')
            ->assertDontSee('4.9 ★')
            ->assertDontSee('Mohammed Al-Otaibi')
            ->assertDontSee('محمد العتيبي')
            ->assertDontSee('Fast and excellent delivery. Package arrived in the same day');
    }

    public function test_rating_only_reviews_do_not_create_fabricated_testimonials(): void
    {
        $shipment = Shipment::factory()->delivered()->create();

        Review::query()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $shipment->customer_id,
            'rating' => 4,
            'comment' => null,
            'status' => 'approved',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('app.home.rating_only'))
            ->assertDontSee('Excellent service, punctual and safe parcel delivery.');
    }

    public function test_registration_calls_to_action_are_hidden_for_authenticated_users(): void
    {
        $customer = User::factory()->role(\App\Enums\UserRoles::Customer)->create();
        $this->actingAs($customer);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('app.home.create_shipment'));

        $this->get(route('services'))
            ->assertOk()
            ->assertDontSee(__('app.pages.start_shipping'));
    }

    public function test_tracking_navigation_calls_to_action_are_hidden_for_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('href="' . route('tracking.form') . '"', false)
            ->assertDontSee(__('app.footer.cta'))
            ->assertSee(__('app.tracking.submit'));
    }

    public function test_tracking_navigation_calls_to_action_are_visible_for_authenticated_users(): void
    {
        $customer = User::factory()->role(\App\Enums\UserRoles::Customer)->create();
        $this->actingAs($customer);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('app.nav.track'))
            ->assertSee(__('app.footer.cta'));
    }

    public function test_public_tracking_shows_status_but_hides_personal_and_internal_details(): void
    {
        $shipment = Shipment::factory()->create([
            'tracking_number' => 'TW-TRACK123',
            'receiver_name' => 'Private Recipient Name',
            'receiver_phone' => '+966500000099',
        ]);
        ShipmentEvent::factory()->create([
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::InTransit->value,
            'description' => 'Private warehouse note.',
        ]);

        $this->post(route('tracking.lookup'), ['tracking_number' => 'tw-track123'])
            ->assertOk()
            ->assertSee('TW-TRACK123')
            ->assertSee(__('app.shipment_statuses.in_transit'))
            ->assertDontSee('Private Recipient Name')
            ->assertDontSee('+966500000099')
            ->assertDontSee('Private warehouse note.');
    }

    public function test_tracking_rejects_malformed_numbers(): void
    {
        $this->post(route('tracking.lookup'), ['tracking_number' => '../private'])
            ->assertSessionHasErrors('tracking_number');
    }

    public function test_contact_form_persists_valid_support_message(): void
    {
        Mail::fake();

        $this->from(route('contact'))->post(route('contact.submit'), [
            'name' => 'Support Customer',
            'email' => 'support@example.test',
            'message' => 'Please help me understand a delivery update.',
        ])->assertRedirect(route('contact') . '#contact');

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail): bool => $mail->hasTo('wkwk00466@gmail.com'));

        $this->assertDatabaseHas('support_messages', [
            'email' => 'support@example.test',
            'status' => 'new',
        ]);
    }

    public function test_contact_page_shows_the_configured_support_inbox(): void
    {
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('mailto:wkwk00466@gmail.com', false)
            ->assertSee('wkwk00466@gmail.com');
    }

    public function test_contact_email_has_localized_html_and_plain_text_versions(): void
    {
        app()->setLocale('ar');

        $mail = new ContactMessageMail(
            senderName: 'Support Customer',
            senderEmail: 'support@example.test',
            messageContent: "Please help.\n<img src=x>",
        );
        $html = $mail->render();

        $this->assertSame(__('app.mail.contact_subject'), $mail->envelope()->subject);
        $this->assertSame('emails.contact-message-text', $mail->content()->text);
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $html);
        $this->assertStringContainsString(__('app.mail.sender_name'), $html);
        $this->assertStringContainsString('Please help.', $html);
        $this->assertStringContainsString('&lt;img src=x&gt;', $html);
        $this->assertStringNotContainsString('<img src=x>', $html);

        app()->setLocale('en');
        $englishMail = new ContactMessageMail('Support Customer', 'support@example.test', 'Please help.');
        $this->assertStringContainsString('<html lang="en" dir="ltr">', $englishMail->render());
    }

    public function test_contact_form_rejects_short_messages(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Support Customer',
            'email' => 'support@example.test',
            'message' => 'Help',
        ])->assertSessionHasErrors('message');

        $this->assertDatabaseCount('support_messages', 0);
    }
}