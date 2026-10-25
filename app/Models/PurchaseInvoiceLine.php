<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'shop_id', 'branch_id', 'purchase_invoice_id', 'product_id',
    'qty', 'unit_cost', 'tax_amount', 'line_total',
])]
class PurchaseInvoiceLine extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'unit_cost' => 'integer',
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

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
