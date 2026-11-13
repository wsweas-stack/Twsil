<?php

namespace App\Models;

use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['driver_id', 'commission_id', 'type', 'amount', 'description'];

    protected $casts = [
        'type' => WalletTransactionType::class,
        'amount' => 'decimal:2',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }
}
