<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('users.index', [
            'users' => collect(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $user): View
    {
        return view('users.form', [
            'user' => (object) [
                'id' => $user,
                'name' => '',
                'email' => '',
                'role' => 'cashier',
                'branch_id' => 1,
                'is_active' => true,
                'branch_ids' => [1],
            ],
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
