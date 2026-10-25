<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'shop_id', 'branch_id', 'register_session_id', 'direction', 'reason', 'amount',
    'reference_type', 'reference_id', 'note', 'created_by',
])]
class RegisterCashMovement extends Model
{
    use SoftDeletes;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
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

    public function registerSession(): BelongsTo
    {
        return $this->belongsTo(RegisterSession::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
