<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\UserRoles;
use App\Models\Commission;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantChatTest extends TestCase
{
    use RefreshDatabase;

    private int $providerStatus = 200;

    /**
     * @var array<string, mixed>
     */
    private array $providerResponse = [
        'choices' => [['message' => ['content' => 'Test assistant reply']]],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleAndPermissionSeeder::class, AdminSeeder::class]);
        config([
            'services.ai.provider' => 'openai',
            'services.openai.key' => 'test-openai-key',
            'services.openai.model' => 'test-model',
        ]);
        Http::fake([
            'api.openai.com/*' => fn () => Http::response($this->providerResponse, $this->providerStatus),
        ]);
    }

    public function test_guest_receives_faq_context_but_never_shipment_records(): void
    {
        $shipment = Shipment::factory()->create(['tracking_number' => 'TW-GUEST123']);

        $this->postJson(route('assistant.chat'), [
            'message' => 'Where is shipment ' . $shipment->tracking_number . '?',
        ])->assertOk()->assertJsonPath('reply', 'Test assistant reply');

        $context = $this->lastProviderContext();

        $this->assertSame('guest', $context['authenticated_role']);
        $this->assertNotEmpty($context['faq_reference']);
        $this->assertSame([], $context['authorized_shipment_information']);
        $this->assertStringNotContainsString($shipment->tracking_number, json_encode($context));
    }

    public function test_gemini_provider_sends_trusted_context_and_returns_reply(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.model' => 'test-gemini-model',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Gemini assistant reply']]],
                ]],
            ]),
        ]);
        $this->withSession(['assistant.conversation.guest' => [
            ['role' => 'user', 'content' => 'Earlier question'],
            ['role' => 'assistant', 'content' => 'Earlier answer'],
        ]]);

        $this->postJson(route('assistant.chat'), [
            'message' => 'General help',
        ])->assertOk()->assertJsonPath('reply', 'Gemini assistant reply');

        $request = Http::recorded()->last()[0];
        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame(['test-gemini-key'], $request->header('x-goog-api-key'));
        $this->assertSame(['user', 'model', 'user'], array_column($request->data()['contents'], 'role'));
        $this->assertStringContainsString(
            'Trusted server context (JSON): ',
            $request->data()['systemInstruction']['parts'][0]['text'],
        );
    }

    public function test_gemini_provider_reports_missing_key(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.key' => null,
        ]);

        $this->postJson(route('assistant.chat'), ['message' => 'General help'])
            ->assertServiceUnavailable()
            ->assertJsonPath('code', 'assistant_not_configured')
            ->assertJsonPath('message', __('app.assistant.not_configured'));
    }

    public function test_customer_can_only_receive_their_own_shipment_information(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $ownShipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'tracking_number' => 'TW-CUST1234',
        ]);
        $otherShipment = Shipment::factory()->create([
            'tracking_number' => 'TW-OTHER123',
        ]);

        $this->actingAs($customer)->postJson(route('assistant.chat'), [
            'message' => 'What is the status of ' . $ownShipment->tracking_number . '?',
        ])->assertOk();

        $context = $this->lastProviderContext();
        $authorized = $context['authorized_shipment_information'];

        $this->assertCount(1, $authorized);
        $this->assertSame($ownShipment->tracking_number, $authorized[0]['tracking_number']);
        $this->assertStringNotContainsString($otherShipment->tracking_number, json_encode($context));
        $this->assertArrayNotHasKey('delivery_fee', $authorized[0]);
        $this->assertArrayNotHasKey('sender_name', $authorized[0]);
    }

    public function test_customer_cannot_learn_if_another_customers_shipment_exists(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $otherShipment = Shipment::factory()->create(['tracking_number' => 'TW-OTHER5678']);

        $this->actingAs($customer)->postJson(route('assistant.chat'), [
            'message' => 'Track ' . $otherShipment->tracking_number,
        ])->assertOk();

        $context = $this->lastProviderContext();
        $this->assertSame([], $context['authorized_shipment_information']);
        $this->assertStringNotContainsString($otherShipment->tracking_number, json_encode($context));
    }

    public function test_driver_can_only_receive_information_for_assigned_shipments(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->create();
        $assigned = Shipment::factory()->create([
            'driver_id' => $driver->id,
            'tracking_number' => 'TW-DRIVER123',
        ]);
        $unassigned = Shipment::factory()->create([
            'tracking_number' => 'TW-NODRIVER1',
        ]);

        $this->actingAs($driver)->postJson(route('assistant.chat'), [
            'message' => "Track {$assigned->tracking_number} and {$unassigned->tracking_number}",
        ])->assertOk();

        $context = $this->lastProviderContext();
        $authorized = $context['authorized_shipment_information'];

        $this->assertCount(1, $authorized);
        $this->assertSame($assigned->tracking_number, $authorized[0]['tracking_number']);
        $this->assertStringNotContainsString($unassigned->tracking_number, json_encode($context));
    }

    public function test_pending_driver_cannot_retrieve_assigned_shipments_or_commissions(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->pending()->create();
        $shipment = Shipment::factory()->create([
            'driver_id' => $driver->id,
            'tracking_number' => 'TW-PENDING123',
        ]);
        Commission::query()->create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'delivery_fee' => 100,
            'driver_commission' => 30,
            'company_share' => 70,
            'driver_commission_rate' => 30,
            'status' => CommissionStatus::Unpaid,
        ]);

        $this->actingAs($driver)->postJson(route('assistant.chat'), [
            'message' => 'Show shipment TW-PENDING123 and my commission',
        ])->assertOk();

        $context = $this->lastProviderContext();
        $this->assertSame([], $context['authorized_shipment_information']);
        $this->assertSame([], $context['authorized_financial_information']);
    }

    public function test_employee_without_shipment_permission_gets_no_shipment_data(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create();
        Shipment::factory()->create(['tracking_number' => 'TW-EMPLOYEE1']);

        $this->actingAs($employee)->postJson(route('assistant.chat'), [
            'message' => 'Show shipment TW-EMPLOYEE1',
        ])->assertOk();

        $this->assertSame([], $this->lastProviderContext()['authorized_shipment_information']);
    }

    public function test_employee_with_shipment_permission_can_access_operational_statuses(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create();
        $employee->givePermissionTo('shipments.view_any');
        $shipment = Shipment::factory()->create(['tracking_number' => 'TW-EMPLOYEE2']);

        $this->actingAs($employee)->postJson(route('assistant.chat'), [
            'message' => 'Show shipment ' . $shipment->tracking_number,
        ])->assertOk();

        $context = $this->lastProviderContext();
        $this->assertSame('employee', $context['authenticated_role']);
        $this->assertSame($shipment->tracking_number, $context['authorized_shipment_information'][0]['tracking_number']);
        $this->assertSame([], $context['authorized_financial_information']);
    }

    public function test_admin_access_is_based_on_seeded_permissions(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $shipment = Shipment::factory()->create(['tracking_number' => 'TW-ADMIN1234']);

        $this->actingAs($admin)->postJson(route('assistant.chat'), [
            'message' => 'Show shipment ' . $shipment->tracking_number,
        ])->assertOk();

        $context = $this->lastProviderContext();
        $this->assertSame('admin', $context['authenticated_role']);
        $this->assertContains('shipments.view_any', $context['permissions']);
        $this->assertSame($shipment->tracking_number, $context['authorized_shipment_information'][0]['tracking_number']);
    }

    public function test_driver_financial_summary_is_scoped_to_the_authenticated_driver(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->create();
        $otherDriver = User::factory()->role(UserRoles::Driver)->create();
        $ownShipment = Shipment::factory()->create();
        $otherShipment = Shipment::factory()->create();

        Commission::query()->create([
            'shipment_id' => $ownShipment->id,
            'driver_id' => $driver->id,
            'delivery_fee' => 100,
            'driver_commission' => 30,
            'company_share' => 70,
            'driver_commission_rate' => 30,
            'status' => CommissionStatus::Unpaid,
        ]);
        Commission::query()->create([
            'shipment_id' => $otherShipment->id,
            'driver_id' => $otherDriver->id,
            'delivery_fee' => 200,
            'driver_commission' => 60,
            'company_share' => 140,
            'driver_commission_rate' => 30,
            'status' => CommissionStatus::Paid,
        ]);

        $this->actingAs($driver)->postJson(route('assistant.chat'), [
            'message' => 'What are my earnings and commission?',
        ])->assertOk();

        $context = $this->lastProviderContext();
        $this->assertEquals([
            'earned' => 30.0,
            'paid' => 0.0,
            'unpaid' => 30.0,
        ], $context['authorized_financial_information']);
    }

    public function test_employee_financial_information_requires_reporting_permission(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create();

        $this->actingAs($employee)->postJson(route('assistant.chat'), [
            'message' => 'Show finance and commission reports',
        ])->assertOk();

        $this->assertSame([], $this->lastProviderContext()['authorized_financial_information']);
    }

    public function test_finance_report_permission_controls_admin_and_employee_aggregates(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $employee = User::factory()->role(UserRoles::Employee)->create();
        $employee->givePermissionTo('finance.view_reports');
        $driver = User::factory()->role(UserRoles::Driver)->create();
        $shipment = Shipment::factory()->delivered()->create(['delivery_fee' => 100]);

        Commission::query()->create([
            'shipment_id' => $shipment->id,
            'driver_id' => $driver->id,
            'delivery_fee' => 100,
            'driver_commission' => 30,
            'company_share' => 70,
            'driver_commission_rate' => 30,
            'status' => CommissionStatus::Unpaid,
        ]);

        foreach ([$admin, $employee] as $user) {
            $this->actingAs($user)->postJson(route('assistant.chat'), [
                'message' => 'Show revenue and commission finance reports',
            ])->assertOk();

            $financial = $this->lastProviderContext()['authorized_financial_information'];
            $this->assertSame(100, $financial['delivered_revenue']);
            $this->assertSame(30, $financial['driver_commissions']);
            $this->assertSame(70, $financial['company_share']);
        }
    }

    public function test_conversation_history_is_server_held_and_isolated_between_users(): void
    {
        $firstCustomer = User::factory()->role(UserRoles::Customer)->create();
        $secondCustomer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $firstCustomer->id,
            'tracking_number' => 'TW-ISOLATE01',
        ]);
        $otherShipment = Shipment::factory()->create([
            'customer_id' => $secondCustomer->id,
            'tracking_number' => 'TW-ISOLATE02',
        ]);

        $this->actingAs($firstCustomer)->postJson(route('assistant.chat'), [
            'message' => 'Hello',
            'history' => [[
                'role' => 'user',
                'content' => 'Track ' . $otherShipment->tracking_number,
            ]],
        ])->assertOk();

        $this->assertSame([], $this->lastProviderContext()['authorized_shipment_information']);

        $this->postJson(route('assistant.chat'), [
            'message' => 'Track ' . $shipment->tracking_number,
        ])->assertOk();

        $this->postJson(route('assistant.chat'), [
            'message' => 'What is its current status?',
        ])->assertOk();

        $firstCustomerContext = $this->lastProviderContext()['authorized_shipment_information'];
        $this->assertSame($shipment->tracking_number, $firstCustomerContext[0]['tracking_number']);

        $this->actingAs($secondCustomer)->postJson(route('assistant.chat'), [
            'message' => 'What is its current status?',
        ])->assertOk();

        $secondCustomerContext = $this->lastProviderContext()['authorized_shipment_information'];
        $this->assertSame([], $secondCustomerContext);
        $this->assertStringNotContainsString($shipment->tracking_number, json_encode($secondCustomerContext));
    }

    public function test_admin_must_supply_a_tracking_number_instead_of_receiving_a_shipment_list(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        Shipment::factory()->create(['tracking_number' => 'TW-ADMINLIST1']);

        $this->actingAs($admin)->postJson(route('assistant.chat'), [
            'message' => 'Show my shipments',
        ])->assertOk();

        $this->assertSame([], $this->lastProviderContext()['authorized_shipment_information']);
    }

    public function test_chat_validation_and_missing_provider_key_are_reported(): void
    {
        $this->postJson(route('assistant.chat'), ['message' => ''])->assertUnprocessable();

        $this->providerStatus = 500;
        $this->providerResponse = ['error' => ['message' => 'provider detail']];

        $this->postJson(route('assistant.chat'), ['message' => 'General help'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', __('app.assistant.unavailable'))
            ->assertJsonMissing(['message' => 'provider detail']);

        config(['services.openai.key' => null]);

        $this->postJson(route('assistant.chat'), ['message' => 'General help'])
            ->assertServiceUnavailable()
            ->assertJsonPath('code', 'assistant_not_configured')
            ->assertJsonPath('message', __('app.assistant.not_configured'));
    }

    public function test_public_and_internal_interfaces_render_the_shared_assistant(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertSee(__('app.assistant.title'))
            ->assertSee(__('app.pages.faqs.0.question'));

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee(__('app.assistant.open'))
            ->assertSee(route('assistant.show'))
            ->assertDontSee('data-ai-assistant');

        $this->get(route('assistant.show'))
            ->assertOk()
            ->assertSee(__('app.assistant.title'))
            ->assertSee('data-full-page="true"', false);

        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee(__('app.assistant.title'));
    }

    /**
     * @return array<string, mixed>
     */
    private function lastProviderContext(): array
    {
        $sentRequest = Http::recorded()->last()[0];

        $this->assertInstanceOf(Request::class, $sentRequest);
        $systemMessage = $sentRequest->data()['messages'][0]['content'];
        $marker = 'Trusted server context (JSON): ';
        $start = strpos($systemMessage, $marker);

        $this->assertNotFalse($start);

        return json_decode(substr($systemMessage, $start + strlen($marker)), true, flags: JSON_THROW_ON_ERROR);
    }
}
