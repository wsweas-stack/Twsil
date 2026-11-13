<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\DB;

class StaffAccessService
{
    public function approve(User $actor, User $staff): void
    {
        $this->authorize($actor, 'users.approve');
        $this->assertPendingStaff($staff);

        DB::transaction(function () use ($staff): void {
            $staff->forceFill([
                'approval_status' => ApprovalStatus::Approved,
                'approved_at' => now(),
                'rejection_reason' => null,
            ])->save();
        });
        $staff->notify(new OperationalNotification(
            'staff_approved',
            'app.notifications.staff_approved_subject',
            'app.notifications.staff_approved',
            url('/admin'),
        ));
    }

    public function reject(User $actor, User $staff, string $reason): void
    {
        $this->authorize($actor, 'users.approve');
        $this->assertPendingStaff($staff);

        DB::transaction(function () use ($staff, $reason): void {
            $staff->forceFill([
                'approval_status' => ApprovalStatus::Rejected,
                'approved_at' => null,
                'rejection_reason' => $reason,
            ])->save();
        });
        $staff->notify(new OperationalNotification(
            'staff_rejected',
            'app.notifications.staff_rejected_subject',
            'app.notifications.staff_rejected',
            route('approval.status'),
            ['reason' => $reason],
        ));
    }

    /** @param array<int, string> $permissions */
    public function assignEmployeePermissions(User $actor, User $employee, array $permissions): void
    {
        $this->authorize($actor, 'users.permissions');
        abort_unless($employee->hasRole(UserRoles::Employee->value), 404);

        $restricted = ['users.approve', 'users.permissions'];
        abort_if(array_intersect($permissions, $restricted) !== [], 422);

        DB::transaction(function () use ($employee, $permissions): void {
            $employee->syncPermissions($permissions);
        });
    }

    private function authorize(User $actor, string $permission): void
    {
        abort_unless(
            $actor->hasRole(UserRoles::Admin->value) && $actor->can($permission),
            403,
        );
    }

    private function assertPendingStaff(User $staff): void
    {
        abort_unless(
            $staff->hasAnyRole([UserRoles::Driver->value, UserRoles::Employee->value])
                && $staff->approval_status === ApprovalStatus::Pending,
            409,
        );
    }
}