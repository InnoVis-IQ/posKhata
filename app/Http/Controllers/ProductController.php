<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('products.index', ['products' => collect()]);
    }

    public function lowStock(): View
    {
        return view('products.low-stock', ['products' => collect()]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => null,
            'categories' => collect(),
            'units' => $this->stubUnits(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $product): View
    {
        return view('products.form', [
            'product' => (object) [
                'id' => $product,
                'name' => '',
                'category_id' => null,
                'unit_id' => 1,
                'sku' => null,
                'barcode' => null,
                'unit_cost' => 0,
                'unit_sale_price' => 0,
                'tax' => 0,
                'unit_discount' => 0,
                'final_unit_price' => 0,
                'batch_size' => 0,
                'batch_discount' => 0,
                'reorder_level' => 0,
                'is_active' => true,
            ],
            'categories' => collect(),
            'units' => $this->stubUnits(),
        ]);
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        return $this->tier3Pending();
    }

    private function stubUnits()
    {
        return collect([
            (object) ['id' => 1, 'name' => 'Piece', 'code' => 'pcs'],
            (object) ['id' => 2, 'name' => 'Kilogram', 'code' => 'kg'],
            (object) ['id' => 3, 'name' => 'Dozen', 'code' => 'dozen'],
        ]);
    }
}
