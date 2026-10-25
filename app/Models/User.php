<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Scopes\HideSuperadminScope;
use App\Support\AuthIdentity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['shop_id', 'branch_id', 'name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_SUPERADMIN = 'superadmin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CASHIER = 'cashier';

    protected static function booted(): void
    {
        static::addGlobalScope(new HideSuperadminScope);
    }

    /**
     * Query including superadmins regardless of who is logged in.
     *
     * @return Builder<static>
     */
    public static function withoutSuperadminScope(): Builder
    {
        return static::withoutGlobalScope(HideSuperadminScope::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** The user's home branch (users.branch_id). */
    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Branches the user may access. branch_user has only created_at, so pass
     * ['created_at' => now()] when attaching (see syncBranchIds()).
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->using(BranchUser::class)
            ->withPivot('created_at');
    }

    /** @param  array<int, int|string>  $branchIds */
    public function syncBranchIds(array $branchIds): array
    {
        $now = now();
        $payload = [];
        foreach ($branchIds as $id) {
            $payload[$id] = ['created_at' => $now];
        }

        return $this->branches()->sync($payload);
    }

    /** Local-part of email; domain is never shown in the UI. */
    public function username(): string
    {
        return AuthIdentity::usernameFromEmail($this->email);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }
}
