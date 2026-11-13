<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentQrCode extends Model
{
    protected $fillable = ['shipment_id', 'token_hash', 'token', 'image_path'];

    protected function casts(): array
    {
        return ['token' => 'encrypted'];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}