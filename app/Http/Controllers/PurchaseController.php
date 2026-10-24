<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('purchases.index', ['invoices' => collect()]);
    }

    public function create(): View
    {
        return view('purchases.form', [
            'invoice' => null,
            'vendors' => collect(),
            'products' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function receive(int $purchase): View
    {
        return view('purchases.receive', [
            'invoice' => (object) [
                'id' => $purchase,
                'invoice_number' => 'PO-'.$purchase,
                'vendor_name' => 'Vendor',
                'lines' => collect(),
            ],
        ]);
    }

    public function storeReceive(Request $request, int $purchase): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
