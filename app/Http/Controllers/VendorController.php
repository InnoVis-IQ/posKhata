<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('vendors.index', ['vendors' => collect()]);
    }

    public function create(): View
    {
        return view('vendors.form', ['vendor' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $vendor): View
    {
        return view('vendors.form', [
            'vendor' => (object) [
                'id' => $vendor,
                'name' => '',
                'phone' => null,
                'email' => null,
                'address' => null,
                'notes' => null,
                'is_active' => true,
            ],
        ]);
    }

    public function update(Request $request, int $vendor): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
