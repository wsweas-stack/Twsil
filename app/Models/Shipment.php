<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'customer_id', 'driver_id', 'tracking_number', 'status', 'delivery_fee', 'currency',
    'sender_name', 'sender_phone', 'sender_city', 'sender_address', 'sender_latitude', 'sender_longitude',
    'receiver_name', 'receiver_phone', 'receiver_city', 'receiver_address', 'receiver_latitude', 'receiver_longitude',
    'receiver_email',
    'description', 'weight_kg', 'invoice_path', 'invoice_extracted_text', 'assigned_at', 'picked_up_at', 'in_transit_at',
    'out_for_delivery_at', 'delivered_at', 'cancelled_at', 'notes',
])]
#[Hidden(['receiver_phone', 'sender_phone'])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory;

    protected $casts = [
        'status' => ShipmentStatus::class,
        'delivery_fee' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'assigned_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'in_transit_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->orderBy('occurred_at');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function deliveryOtp(): HasOne
    {
        return $this->hasOne(DeliveryOtp::class);
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(ShipmentQrCode::class);
    }
}
