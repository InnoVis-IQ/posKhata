<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'shop_id', 'branch_id', 'customer_id', 'register_session_id', 'sale_number', 'status',
    'subtotal', 'discount_total', 'tax_total', 'total', 'sold_at', 'cashier_id', 'notes',
])]
class Sale extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_total' => 'integer',
            'total' => 'integer',
            'sold_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(SaleRefund::class);
    }
}
