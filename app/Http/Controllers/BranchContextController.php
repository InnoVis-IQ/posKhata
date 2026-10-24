<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    /**
     * Sets session current_branch_id after checking the branch is an active branch of the
     * user's shop that the user may access (admins/superadmins: any; others: home or assigned).
     */
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate(['branch_id' => ['required', 'integer']]);

        $user = PosContext::user();
        $branch = Branch::query()
            ->whereKey((int) $data['branch_id'])
            ->where('shop_id', PosContext::shopId())
            ->where('is_active', true)
            ->first();

        $allowed = $branch !== null && (
            PosContext::isAdmin()
            || (int) $user->branch_id === (int) $branch->id
            || $user->branches()->whereKey($branch->id)->exists()
        );

        if (! $allowed) {
            return back()->withErrors(['error' => 'You do not have access to that branch.']);
        }

        session(['current_branch_id' => $branch->id]);

        return back()->with('status', "Switched to {$branch->name}.");
    }
}
