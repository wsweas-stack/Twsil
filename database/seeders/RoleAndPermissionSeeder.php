<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'shipments.view_any',
            'shipments.create',      // staff-created shipments (mirror of public creation)
            'shipments.update',
            'shipments.assign_driver',
            'shipments.cancel',
            'payments.view_any',
            'payments.verify',
            'reviews.moderate',
            'users.view_any',
            'users.approve',         // driver/employee approval workflow
            'users.permissions',     // assign permissions to employees
            'finance.view_reports',
            'finance.pay_commissions',
            'finance.configure_rates',
            'shipments.pickup',      // QR pickup scan
            'shipments.deliver',     // OTP delivery confirmation
            'shipments.update_status',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(Permission::all());

        // Drivers get operational permissions; Employees receive access only when Admin assigns it.
        Role::findOrCreate('driver', 'web')->givePermissionTo([
            'dashboard.view', 'shipments.pickup', 'shipments.deliver', 'shipments.update_status',
        ]);

        Role::findOrCreate('employee', 'web');

        Role::findOrCreate('customer', 'web');
    }
}
