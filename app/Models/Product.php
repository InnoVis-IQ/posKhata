<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Money columns (unit_cost, unit_sale_price, tax, unit_discount, final_unit_price,
 * batch_discount) are integer minor units. unit_id is required (NOT NULL, RESTRICT).
 */
#[Fillable([
    'shop_id', 'category_id', 'unit_id', 'name', 'sku', 'barcode',
    'unit_cost', 'unit_sale_price', 'tax', 'unit_discount', 'final_unit_price',
    'batch_size', 'batch_discount', 'reorder_level', 'is_active',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'unit_cost' => 'integer',
            'unit_sale_price' => 'integer',
            'tax' => 'integer',
            'unit_discount' => 'integer',
            'final_unit_price' => 'integer',
            'batch_size' => 'decimal:3',
            'batch_discount' => 'integer',
            'reorder_level' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockLedgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }

    public function saleLines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function purchaseInvoiceLines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class);
    }
}
