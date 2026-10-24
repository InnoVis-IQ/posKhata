<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Hides superadmin users from every User query unless the authenticated user is
 * itself a superadmin. Not applied when nobody is authenticated (e.g. login).
 *
 * Bypass explicitly with User::withoutSuperadminScope() or
 * ->withoutGlobalScope(HideSuperadminScope::class).
 */
class HideSuperadminScope implements Scope
{
    /** Re-entrancy guard: resolving the auth user itself queries User. */
    private static bool $resolving = false;

    public function apply(Builder $builder, Model $model): void
    {
        if (self::$resolving) {
            return;
        }

        self::$resolving = true;

        try {
            $user = auth()->check() ? auth()->user() : null;
        } finally {
            self::$resolving = false;
        }

        if ($user !== null && $user->role !== 'superadmin') {
            $builder->where($model->qualifyColumn('role'), '!=', 'superadmin');
        }
    }
}
