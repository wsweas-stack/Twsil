<?php

namespace App\Filament\Pages;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Models\User;
use App\Services\StaffAccessService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

class ManageApprovals extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.manage-approvals';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Staff approvals';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.admin_nav.approvals');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole(UserRoles::Admin->value);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()
                ->where(function (Builder $query): void {
                    $query->where(function (Builder $pending): void {
                        $pending->where('approval_status', ApprovalStatus::Pending)
                            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', [
                                UserRoles::Driver->value,
                                UserRoles::Employee->value,
                            ]));
                    })->orWhere(function (Builder $approvedStaff): void {
                        $approvedStaff->where('approval_status', ApprovalStatus::Approved)
                            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', [
                                UserRoles::Driver->value,
                                UserRoles::Employee->value,
                            ]));
                    });
                }))
            ->columns([
                TextColumn::make('name')->label(__('app.admin.name'))->searchable(),
                TextColumn::make('email')->label(__('app.admin.email'))->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('app.admin.account_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('app.auth.role_' . $state)),
                TextColumn::make('phone')->label(__('app.auth.phone')),
                TextColumn::make('city')->label(__('app.auth.city')),
                TextColumn::make('national_id')->label(__('app.auth.national_id')),
                TextColumn::make('approval_status')->label(__('تفاصيل الصلاحيات'))->badge()
                    ->formatStateUsing(fn (ApprovalStatus $state): string => $state->label()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('app.admin.approve'))
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->approval_status === ApprovalStatus::Pending)
                    ->requiresConfirmation()
                    ->action(fn (User $record) => app(StaffAccessService::class)->approve($this->actor(), $record)),
                Action::make('reject')
                    ->label(__('app.admin.reject'))
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->approval_status === ApprovalStatus::Pending)
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label(__('app.admin.rejection_reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(fn (User $record, array $data) => app(StaffAccessService::class)->reject(
                        $this->actor(),
                        $record,
                        $data['rejection_reason'],
                    )),
                Action::make('permissions')
                    ->label(__('app.admin.permissions'))
                    ->visible(fn (User $record): bool => $record->hasRole(UserRoles::Employee->value))
                    ->fillForm(fn (User $record): array => [
                        'permissions' => $record->getPermissionNames()->all(),
                    ])
                    ->form([
                        CheckboxList::make('permissions')
                            ->label(__('app.admin.permissions'))
                            ->options(fn (): array => Permission::query()
                                ->where('guard_name', 'web')
                                ->whereNotIn('name', ['users.approve', 'users.permissions'])
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->all())
                            ->columns(2)
                            ->searchable(),
                    ])
                    ->action(fn (User $record, array $data) => app(StaffAccessService::class)
                        ->assignEmployeePermissions($this->actor(), $record, $data['permissions'] ?? [])),
            ])
            ->emptyStateHeading(__('app.admin.no_pending_staff'))
            ->emptyStateDescription(__('app.admin.no_pending_staff_description'));
    }

    private function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
