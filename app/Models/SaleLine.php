<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'shop_id', 'branch_id', 'sale_id', 'product_id', 'qty',
    'unit_price', 'unit_cost', 'discount_amount', 'tax_amount', 'line_total',
])]
class SaleLine extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'unit_price' => 'integer',
            'unit_cost' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'line_total' => 'integer',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
