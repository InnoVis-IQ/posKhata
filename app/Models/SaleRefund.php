<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'shop_id', 'branch_id', 'sale_id', 'kind', 'status', 'restock', 'reason',
    'requested_by', 'approved_by', 'approved_at', 'subtotal', 'tax_total', 'total',
])]
class SaleRefund extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'restock' => 'boolean',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleRefundLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SaleRefundPayment::class);
    }
}
