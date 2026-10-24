<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Tier 2 preview login — sets session viewer for nav/role demos.
     * Tier 3 replaces with real Auth::attempt (unscoped for superadmin).
     */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:superadmin,admin,cashier'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        session([
            'viewer.name' => $data['name'] ?: match ($data['role']) {
                'superadmin' => 'Superadmin',
                'cashier' => 'Cashier',
                default => 'Admin',
            },
            'viewer.email' => $data['email'],
            'viewer.role' => $data['role'],
            'viewer.preview' => true,
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget(['viewer.name', 'viewer.email', 'viewer.role', 'viewer.preview']);

        return redirect()->route('login');
    }
}
