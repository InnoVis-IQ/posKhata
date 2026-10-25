<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'branch_id', 'purchase_receiving_id', 'product_id', 'qty', 'unit_cost'])]
class PurchaseReceivingLine extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'unit_cost' => 'integer',
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

    public function purchaseReceiving(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiving::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
