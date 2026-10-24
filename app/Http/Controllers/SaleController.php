<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SaleController extends Controller
{
    public function receipt(int $sale): View
    {
        return view('sales.receipt', [
            'sale' => (object) [
                'id' => $sale,
                'sale_number' => 'S-'.str_pad((string) $sale, 5, '0', STR_PAD_LEFT),
                'sold_at' => now(),
                'subtotal' => 0,
                'discount_total' => 0,
                'tax_total' => 0,
                'total' => 0,
                'cashier_name' => 'Cashier',
                'customer_name' => null,
                'lines' => collect(),
                'payments' => collect(),
            ],
        ]);
    }
}
