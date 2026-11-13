<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name', 'email', 'password', 'phone', 'approval_status', 'national_id', 'city',
    'vehicle_type', 'license_number', 'rejection_reason', 'approved_at',
])]
#[Hidden(['password', 'remember_token', 'rejection_reason'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guarded = []; // authorization enforced through policies, never mass-assignment of roles

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approval_status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // Domain queries
    // ------------------------------------------------------------------

    public function hasInternalAccess(): bool
    {
        return $this->hasAnyRole([UserRoles::Admin->value, UserRoles::Driver->value, UserRoles::Employee->value])
            && $this->approval_status === ApprovalStatus::Approved;
    }

    public function isApprovedInternalUser(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved
            && $this->hasAnyRole([UserRoles::Driver->value, UserRoles::Employee->value]);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->hasInternalAccess();
    }

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    /** @return HasMany<Shipment, $this> */
    public function customerShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'customer_id');
    }

    /** @return HasMany<Shipment, $this> */
    public function assignedShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'driver_id');
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<WalletTransaction, $this> */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'driver_id');
    }

    /** @return HasMany<Commission, $this> */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'driver_id');
    }
}
