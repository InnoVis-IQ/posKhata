<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorPaymentController extends Controller
{
    use Tier3Stub;

    public function create(): View
    {
        return view('vendor-payments.form', [
            'vendors' => collect(),
            'invoices' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
