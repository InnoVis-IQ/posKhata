<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Append-only ledger: correct mistakes with a reversing entry (reverses_id). */
#[Fillable([
    'shop_id', 'branch_id', 'product_id', 'qty_delta', 'movement_type',
    'reference_type', 'reference_id', 'reverses_id', 'note', 'created_by',
])]
class StockLedgerEntry extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'qty_delta' => 'decimal:3',
            'reference_id' => 'integer',
            'created_at' => 'datetime',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
