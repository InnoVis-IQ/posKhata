<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    use Tier3Stub;

    public function create(): View
    {
        return view('purchase-returns.form', [
            'vendors' => collect(),
            'products' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
