<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait Tier3Stub
{
    protected function tier3Pending(?string $message = null): RedirectResponse
    {
        return back()->with(
            'warning',
            $message ?? 'This action will be wired in Tier 3 (backend).'
        );
    }
}
