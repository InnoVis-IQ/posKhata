<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\PosContext;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function receipt(int $sale): View
    {
        $model = Sale::query()
            ->where('shop_id', PosContext::shopId())
            ->with(['lines.product', 'payments', 'cashier', 'customer'])
            ->findOrFail($sale);

        $model->lines->each(fn ($line) => $line->setAttribute('name', $line->product?->name ?? 'Item #'.$line->product_id));
        $model->setAttribute('cashier_name', $model->cashier?->name);
        $model->setAttribute('customer_name', $model->customer?->name);

        return view('sales.receipt', ['sale' => $model]);
    }
}
