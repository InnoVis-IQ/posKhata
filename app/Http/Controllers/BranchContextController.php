<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $request->validate(['branch_id' => ['required', 'integer']]);
        session(['current_branch_id' => (int) $request->input('branch_id')]);

        return back()->with('status', 'Branch selection saved for this preview session.');
    }
}
