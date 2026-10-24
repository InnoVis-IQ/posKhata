<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the acting user / shop / branch for the current request.
 *
 * Branch resolution order:
 *   1. session('current_branch_id') - only if it belongs to the user's shop, is active
 *      and the user may access it (admins/superadmins: any shop branch; others: home
 *      branch or a branch_user assignment).
 *   2. users.branch_id (must belong to the shop).
 *   3. Admins/superadmins only: the shop's default (or first active) branch.
 */
final class PosContext
{
    public static function user(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new \RuntimeException('No authenticated user: POS context is unavailable.');
        }

        return $user;
    }

    public static function shop(): Shop
    {
        $user = self::user();
        $shop = $user->shop;

        if (! $shop instanceof Shop) {
            throw new \RuntimeException("User #{$user->id} is not attached to a shop.");
        }

        return $shop;
    }

    public static function shopId(): int
    {
        return (int) self::user()->shop_id;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user->isAdmin() || $user->isSuperadmin();
    }

    public static function branchId(): int
    {
        return self::branch()->id;
    }

    public static function branch(): Branch
    {
        $user = self::user();
        $shopId = (int) $user->shop_id;

        $sessionBranchId = session('current_branch_id');
        if ($sessionBranchId !== null && $sessionBranchId !== '') {
            $branch = self::findShopBranch((int) $sessionBranchId, $shopId);
            if ($branch !== null && self::canAccess($user, $branch)) {
                return $branch;
            }
        }

        if ($user->branch_id !== null) {
            $branch = self::findShopBranch((int) $user->branch_id, $shopId);
            if ($branch === null) {
                throw new \RuntimeException(
                    "User #{$user->id} home branch #{$user->branch_id} does not belong to shop #{$shopId} or is inactive."
                );
            }

            return $branch;
        }

        if (self::isAdmin()) {
            $branch = Branch::query()
                ->where('shop_id', $shopId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();

            if ($branch !== null) {
                return $branch;
            }
        }

        throw new \RuntimeException("Unable to resolve a branch for user #{$user->id}: no branch assigned.");
    }

    private static function findShopBranch(int $branchId, int $shopId): ?Branch
    {
        return Branch::query()
            ->whereKey($branchId)
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->first();
    }

    private static function canAccess(User $user, Branch $branch): bool
    {
        if ($user->isAdmin() || $user->isSuperadmin()) {
            return true;
        }

        if ((int) $user->branch_id === (int) $branch->id) {
            return true;
        }

        return $user->branches()->whereKey($branch->id)->exists();
    }
}
