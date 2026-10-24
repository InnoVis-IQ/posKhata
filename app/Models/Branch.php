<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['shop_id', 'name', 'code', 'address', 'phone', 'is_default', 'is_active'])]
class Branch extends Model
{
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Users with access to this branch via branch_user. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user')
            ->using(BranchUser::class)
            ->withPivot('created_at');
    }

    /** Users whose home branch (users.branch_id) is this branch. */
    public function homeUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function registerSessions(): HasMany
    {
        return $this->hasMany(RegisterSession::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function stockLedgerEntries(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }
}
