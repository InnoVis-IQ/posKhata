<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'name', 'phone', 'email', 'address', 'khaata_warning_amount', 'is_active'])]
class Customer extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'khaata_warning_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function khaataEntries(): HasMany
    {
        return $this->hasMany(KhaataEntry::class);
    }

    public function khaataPayments(): HasMany
    {
        return $this->hasMany(KhaataPayment::class);
    }
}
