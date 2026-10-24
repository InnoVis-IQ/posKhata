<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('pos.index', [
            'products' => collect([
                (object) [
                    'id' => 1,
                    'name' => 'Sample Item',
                    'sku' => 'SKU-1',
                    'barcode' => '1001',
                    'unit' => 'pcs',
                    'unit_id' => 1,
                    'final_unit_price' => 10000,
                    'batch_size' => 0,
                    'batch_discount' => 0,
                ],
                (object) [
                    'id' => 2,
                    'name' => 'Demo Biscuits',
                    'sku' => 'SKU-2',
                    'barcode' => '1002',
                    'unit' => 'pcs',
                    'unit_id' => 1,
                    'final_unit_price' => 2500,
                    'batch_size' => 12,
                    'batch_discount' => 2000,
                ],
            ]),
            'customers' => collect(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
