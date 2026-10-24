<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    use Tier3Stub;

    public function opening(): View
    {
        return view('stock.opening', ['products' => collect()]);
    }

    public function storeOpening(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function adjustments(): View
    {
        return view('stock.adjustments', ['products' => collect()]);
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function transfers(): View
    {
        return view('stock.transfers.index', ['transfers' => collect()]);
    }

    public function createTransfer(): View
    {
        return view('stock.transfers.form', ['products' => collect()]);
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
