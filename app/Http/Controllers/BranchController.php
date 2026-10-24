<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('branches.index', [
            'items' => collect([
                (object) [
                    'id' => 1,
                    'name' => 'Main',
                    'code' => 'MAIN',
                    'is_default' => true,
                    'is_active' => true,
                    'phone' => null,
                ],
            ]),
        ]);
    }

    public function create(): View
    {
        return view('branches.form', ['branch' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $branch): View
    {
        return view('branches.form', [
            'branch' => (object) [
                'id' => $branch,
                'name' => 'Main',
                'code' => 'MAIN',
                'address' => null,
                'phone' => null,
                'is_default' => true,
                'is_active' => true,
            ],
        ]);
    }

    public function update(Request $request, int $branch): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
