<?php

namespace Tests\Feature;

use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_customer_can_update_profile_without_changing_role_or_identity(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create([
            'email' => 'profile@example.test',
        ]);
        $other = User::factory()->role(UserRoles::Customer)->create();

        $this->actingAs($customer)
            ->put(route('account.profile.update'), [
                'name' => 'Updated Customer',
                'email' => 'profile-new@example.test',
                'phone' => '+966500000123',
                'id' => $other->id,
                'role' => UserRoles::Admin->value,
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame('Updated Customer', $customer->name);
        $this->assertSame('profile-new@example.test', $customer->email);
        $this->assertSame('+966500000123', $customer->phone);
        $this->assertTrue($customer->hasRole(UserRoles::Customer->value));
        $this->assertFalse($customer->hasRole(UserRoles::Admin->value));
        $this->assertNotSame('Updated Customer', $other->fresh()->name);
    }

    public function test_password_change_requires_current_password(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create([
            'password' => 'current-password',
        ]);

        $this->actingAs($customer)
            ->put(route('account.profile.update'), [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'current_password' => 'incorrect-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-password', $customer->fresh()->password));
    }

    public function test_profile_shows_password_confirmation_error_under_its_field(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create([
            'password' => 'current-password',
        ]);

        $this->actingAs($customer)
            ->from(route('account.profile.edit'))
            ->put(route('account.profile.update'), [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'current_password' => 'current-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect(route('account.profile.edit'))
            ->assertSessionHasErrors('password_confirmation');

        $this->get(route('account.profile.edit'))
            ->assertOk()
            ->assertSee('id="password-confirmation-error"', false);
    }

    public function test_customer_can_change_password_after_current_password_verification(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create([
            'password' => 'current-password',
        ]);

        $this->actingAs($customer)
            ->put(route('account.profile.update'), [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'current_password' => 'current-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $customer->fresh()->password));
    }

    public function test_non_customer_cannot_open_customer_profile_settings(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->create();

        $this->actingAs($driver)->get(route('account.profile.edit'))->assertForbidden();
    }
}