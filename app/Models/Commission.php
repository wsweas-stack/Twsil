<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id', 'driver_id', 'delivery_fee', 'driver_commission', 'company_share',
        'driver_commission_rate', 'status', 'paid_at', 'payment_notes',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'driver_commission' => 'decimal:2',
        'company_share' => 'decimal:2',
        'driver_commission_rate' => 'decimal:2',
        'status' => CommissionStatus::class,
        'paid_at' => 'datetime',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
