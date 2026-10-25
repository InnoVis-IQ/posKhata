<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Support\PosContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();
        $shop = PosContext::shop();
        $weekStart = now()->subDays(7)->startOfDay();
        $today = now()->toDateString();

        $weekSales = Sale::query()
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->where('sold_at', '>=', $weekStart)
            ->where('status', '!=', 'void')
            ->selectRaw('coalesce(sum(total), 0) as amount, count(*) as sale_count')
            ->first();

        $todaySalesAmount = (int) Sale::query()
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereDate('sold_at', $today)
            ->where('status', '!=', 'void')
            ->sum('total');

        $lowStock = $this->lowStock($shopId, $branchId);
        $creditAlerts = $this->creditAlerts($shopId, (int) ($shop->default_khaata_warning_amount ?? 0));

        return view('dashboard', [
            'weekSalesAmount' => (int) ($weekSales->amount ?? 0),
            'weekSaleCount' => (int) ($weekSales->sale_count ?? 0),
            'todaySalesAmount' => $todaySalesAmount,
            'lowStockCount' => $lowStock->count(),
            'lowStock' => $lowStock->take(8),
            'creditAlertCount' => $creditAlerts->count(),
            'creditAlerts' => $creditAlerts->take(8),
            'topSellers' => $this->topSellers($shopId, $branchId, $weekStart),
        ]);
    }

    /** Active products at this branch whose on-hand qty is at or below reorder level. */
    private function lowStock(int $shopId, int $branchId): Collection
    {
        $qtySql = '(select coalesce(sum(sle.qty_delta), 0) from stock_ledger_entries sle '
            .'where sle.product_id = products.id and sle.branch_id = ?)';

        return Product::query()
            ->where('products.shop_id', $shopId)
            ->where('products.is_active', true)
            ->select('products.id', 'products.name', 'products.reorder_level')
            ->selectRaw("{$qtySql} as qty", [$branchId])
            ->whereRaw("{$qtySql} <= products.reorder_level", [$branchId])
            ->orderBy('products.name')
            ->get();
    }

    /**
     * Active customers whose khaata balance is at least 80% of their warning threshold
     * (per-customer override, else shop default). Threshold 0 = no warning.
     */
    private function creditAlerts(int $shopId, int $defaultThreshold): Collection
    {
        return Customer::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->withSum('khaataEntries as balance', 'amount_delta')
            ->orderBy('name')
            ->get()
            ->map(function (Customer $customer) use ($defaultThreshold) {
                $threshold = (int) ($customer->khaata_warning_amount ?? $defaultThreshold);
                $balance = (int) ($customer->balance ?? 0);
                $warnAt = $threshold > 0 ? (int) round($threshold * 0.8) : 0;

                $customer->setAttribute('balance', $balance);
                $customer->setAttribute('threshold', $threshold);
                $customer->setAttribute('warn_at', $warnAt);
                $customer->setAttribute(
                    'pct',
                    $threshold > 0 ? (int) min(999, round(($balance / $threshold) * 100)) : 0
                );

                return $customer;
            })
            ->filter(fn (Customer $c) => $c->threshold > 0 && $c->balance >= $c->warn_at)
            ->sortByDesc('pct')
            ->values();
    }

    /** Top 5 products by qty sold at this branch over the past 7 days. */
    private function topSellers(int $shopId, int $branchId, CarbonInterface $weekStart): Collection
    {
        return SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sale_lines.shop_id', $shopId)
            ->where('sale_lines.branch_id', $branchId)
            ->where('sales.sold_at', '>=', $weekStart)
            ->where('sales.status', '!=', 'void')
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_lines.deleted_at')
            ->groupBy('sale_lines.product_id', 'products.name')
            ->selectRaw('sale_lines.product_id, products.name, coalesce(sum(sale_lines.qty), 0) as qty_sold, coalesce(sum(sale_lines.line_total), 0) as revenue')
            ->orderByDesc('qty_sold')
            ->limit(5)
            ->get();
    }
}
