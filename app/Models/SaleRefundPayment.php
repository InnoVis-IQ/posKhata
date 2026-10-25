<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['shop_id', 'branch_id', 'sale_refund_id', 'method', 'amount', 'register_session_id'])]
class SaleRefundPayment extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
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

    public function saleRefund(): BelongsTo
    {
        return $this->belongsTo(SaleRefund::class);
    }

    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }
}
