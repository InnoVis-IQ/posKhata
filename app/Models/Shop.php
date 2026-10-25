<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'logo_path', 'brand_tag', 'address', 'phone', 'receipt_header', 'receipt_footer',
    'currency_code', 'currency_minor_units', 'tax_rate_bps', 'default_khaata_warning_amount',
    'multi_branch_enabled',
])]
class Shop extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'currency_minor_units' => 'integer',
            'tax_rate_bps' => 'integer',
            'default_khaata_warning_amount' => 'decimal:2',
            'multi_branch_enabled' => 'boolean',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
