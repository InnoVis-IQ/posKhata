<?php

namespace App\Http\Controllers\Concerns;

use App\Support\PosContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Role gates for controllers implementing HasMiddleware, plus a helper to bounce
 * domain errors (RuntimeException / InvalidArgumentException) back with a message.
 *
 * Controller middleware runs after the route's `pos.auth`, so the user is resolved here.
 */
trait GatesRoles
{
    /** admin or superadmin */
    protected static function adminOnly(): Middleware
    {
        return new Middleware(function (Request $request, Closure $next) {
            abort_unless(PosContext::isAdmin(), 403, 'Only an admin can do this.');

            return $next($request);
        });
    }

    /** superadmin only */
    protected static function superadminOnly(): Middleware
    {
        return new Middleware(function (Request $request, Closure $next) {
            abort_unless(PosContext::user()->isSuperadmin(), 403, 'Only a superadmin can do this.');

            return $next($request);
        });
    }

    protected function backWithError(\Throwable $e): RedirectResponse
    {
        return back()->withInput()->withErrors(['error' => $e->getMessage()]);
    }
}
