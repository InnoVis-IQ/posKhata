<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    use Tier3Stub;

    public function __construct()
    {
        // Tier 2 preview gate; Tier 3 will use policies.
        if (session('viewer.role', 'admin') !== 'superadmin') {
            abort(403, 'Only superadmin can manage units.');
        }
    }

    public function index(): View
    {
        return view('units.index', [
            'units' => collect([
                (object) ['id' => 1, 'name' => 'Piece', 'code' => 'pcs', 'is_active' => true],
                (object) ['id' => 2, 'name' => 'Kilogram', 'code' => 'kg', 'is_active' => true],
                (object) ['id' => 3, 'name' => 'Dozen', 'code' => 'dozen', 'is_active' => true],
            ]),
        ]);
    }

    public function create(): View
    {
        return view('units.form', ['unit' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $unit): View
    {
        return view('units.form', [
            'unit' => (object) [
                'id' => $unit,
                'name' => '',
                'code' => '',
                'is_active' => true,
            ],
        ]);
    }

    public function update(Request $request, int $unit): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
