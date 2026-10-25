<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'shop_id', 'branch_id', 'opened_by', 'opened_at', 'opening_float', 'status',
    'closed_by', 'closed_at', 'expected_cash', 'counted_cash', 'variance',
    'reopened_by', 'reopened_at',
])]
class RegisterSession extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
            'opening_float' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(RegisterCashMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function khaataPayments(): HasMany
    {
        return $this->hasMany(KhaataPayment::class);
    }

    public function vendorPayments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }
}
