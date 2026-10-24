<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareUiContext
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('viewer', (object) [
            'name' => session('viewer.name', 'Demo Admin'),
            'email' => session('viewer.email', 'admin@poskhata.local'),
            'role' => session('viewer.role', 'admin'),
        ]);

        View::share('shop', (object) [
            'name' => session('shop.name', 'posKhata Shop'),
            'logo_path' => session('shop.logo_path'),
            'brand_tag' => session('shop.brand_tag'),
            'address' => session('shop.address'),
            'phone' => session('shop.phone'),
            'receipt_header' => session('shop.receipt_header'),
            'receipt_footer' => session('shop.receipt_footer'),
            'currency_code' => session('shop.currency_code', 'PKR'),
            'currency_minor_units' => (int) session('shop.currency_minor_units', 2),
            'tax_rate_bps' => (int) session('shop.tax_rate_bps', 0),
            'default_khaata_warning_amount' => (int) session('shop.default_khaata_warning_amount', 0),
            'multi_branch_enabled' => (bool) session('shop.multi_branch_enabled', false),
        ]);

        $branches = collect([
            (object) ['id' => 1, 'name' => 'Main', 'code' => 'MAIN'],
        ]);

        View::share('branches', $branches);
        View::share('currentBranch', $branches->first());
        View::share('registerOpen', (bool) session('register_open', false));

        return $next($request);
    }
}
