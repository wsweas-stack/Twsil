<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_public_registration_rejects_admin_role(): void
    {
        $this->post('/register', $this->registrationData([
            'role' => UserRoles::Admin->value,
        ]))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_shows_password_confirmation_error_under_its_field(): void
    {
        $this->from(route('register'))
            ->post(route('register'), $this->registrationData([
                'password_confirmation' => 'different-password',
            ]))
            ->assertRedirect(route('register'));

        $this->get(route('register'))
            ->assertOk()
            ->assertViewHas('errors', fn ($errors) => $errors->has('password_confirmation'))
            ->assertSee('id="password-confirmation-error"', false);
    }

    public function test_customer_registration_is_immediately_approved_and_stays_out_of_filament(): void
    {
        $this->post('/register', $this->registrationData())
            ->assertRedirect(route('account.dashboard'));

        $customer = User::query()->where('email', 'customer@example.test')->firstOrFail();

        $this->assertSame(ApprovalStatus::Approved, $customer->approval_status);
        $this->assertTrue($customer->hasRole(UserRoles::Customer->value));
        $this->assertAuthenticatedAs($customer);
        $this->get('/admin')->assertForbidden();
        $this->get(route('account.dashboard'))->assertOk();
        $this->get(route('approval.status'))->assertForbidden();
    }

    public function test_driver_registration_requires_profile_details_and_starts_pending(): void
    {
        $this->post('/register', $this->registrationData([
            'role' => UserRoles::Driver->value,
            'national_id' => 'ID-1001',
            'city' => 'Riyadh',
            'vehicle_type' => 'Van',
            'license_number' => 'LIC-1001',
        ]))
            ->assertRedirect(route('approval.status'));

        $driver = User::query()->where('email', 'customer@example.test')->firstOrFail();

        $this->assertSame(ApprovalStatus::Pending, $driver->approval_status);
        $this->assertTrue($driver->hasRole(UserRoles::Driver->value));
        $this->assertNull($driver->approved_at);
        $this->get('/admin')->assertForbidden();
        $this->get(route('approval.status'))
            ->assertOk()
            ->assertSee('href="' . route('approval.status') . '"', false)
            ->assertSee(__('app.auth.approval_title'))
            ->assertDontSee('href="' . route('account.dashboard') . '"', false);
        $this->get(route('account.dashboard'))->assertForbidden();
    }

    public function test_approved_staff_header_links_to_the_internal_panel(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);

        $this->actingAs($driver)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('href="' . url('/admin') . '"', false)
            ->assertSee(__('app.dashboard.title'));
    }

    public function test_customer_can_sign_in_and_sign_out(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create([
            'email' => 'login@example.test',
            'password' => 'password1234',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('btn-header-logout', false);

        $this->post(route('language.update'), ['locale' => 'en']);

        $this->post('/login', [
            'email' => 'login@example.test',
            'password' => 'password1234',
        ])->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($customer);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee('action="' . route('logout') . '"', false)
            ->assertSee('aria-label="' . __('app.auth.logout') . '"', false);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false);
    }

    public function test_seeded_admin_can_access_the_single_internal_panel(): void
    {
        $this->seed(AdminSeeder::class);

        $this->post('/login', [
            'email' => 'admin@tawsili.test',
            'password' => env('ADMIN_PASSWORD', 'admin123456'),
        ])->assertRedirect('/admin');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('fi-account-widget-user-name', false)
            ->assertDontSee('fi-account-widget-heading', false);
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="' . url('/admin') . '"', false);
    }

    public function test_seeded_admin_is_redirected_to_home_after_filament_logout(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();

        $this->actingAs($admin)
            ->post('/admin/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_public_locale_switch_updates_document_direction(): void
    {
        config(['app.locale' => 'ar']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false);

        $this->post(route('language.update'), ['locale' => 'en'])->assertRedirect();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false);
    }

    public function test_locale_validation_error_is_shown_by_the_language_selector(): void
    {
        $this->from(route('home'))
            ->post(route('language.update'), ['locale' => 'fr'])
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('errors', fn ($errors) => $errors->has('locale'))
            ->assertSee('id="locale-error"', false);
    }

    public function test_employee_registration_starts_pending(): void
    {
        $this->post('/register', $this->registrationData([
            'role' => UserRoles::Employee->value,
            'national_id' => 'ID-2001',
            'city' => 'Jeddah',
        ]))->assertRedirect(route('approval.status'));

        $employee = User::query()->where('email', 'customer@example.test')->firstOrFail();

        $this->assertSame(ApprovalStatus::Pending, $employee->approval_status);
        $this->assertTrue($employee->hasRole(UserRoles::Employee->value));
        $this->get('/admin')->assertForbidden();
        $this->get(route('approval.status'))
            ->assertOk()
            ->assertSee('href="' . route('approval.status') . '"', false)
            ->assertDontSee('href="' . route('account.dashboard') . '"', false);
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Account',
            'email' => 'customer@example.test',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
            'role' => UserRoles::Customer->value,
            'phone' => '+966500000001',
        ], $overrides);
    }
}