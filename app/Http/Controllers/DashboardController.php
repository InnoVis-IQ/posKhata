<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Support\PosContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();
        $shop = PosContext::shop();
        $days30 = now()->subDays(30)->startOfDay();
        $yearStart = now()->subYear()->startOfDay();

        $lowStock = $this->lowStock($shopId, $branchId);
        $creditAlerts = $this->creditAlerts($shopId, (int) ($shop->default_khaata_warning_amount ?? 0));

        return view('dashboard', [
            'lowStock' => $lowStock->take(10),
            'creditAlerts' => $creditAlerts->take(10),
            'weekDailySales' => $this->weekDailySales($shopId, $branchId),
            'topSellers' => $this->topSellers($shopId, $branchId, $days30),
            'valuedCustomers' => $this->valuedCustomers($shopId, $branchId, $days30, $yearStart),
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

    /** Daily sales totals for the past 7 days (including today), voids excluded. */
    private function weekDailySales(int $shopId, int $branchId): Collection
    {
        $start = now()->subDays(6)->startOfDay();

        $byDay = Sale::query()
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->where('sold_at', '>=', $start)
            ->where('status', '!=', 'void')
            ->selectRaw('date(sold_at) as day, coalesce(sum(total), 0) as amount')
            ->groupBy(DB::raw('date(sold_at)'))
            ->pluck('amount', 'day');

        return collect(range(0, 6))->map(function (int $i) use ($start, $byDay) {
            $day = $start->copy()->addDays($i);

            return (object) [
                'date' => $day->toDateString(),
                'label' => $day->format('D'),
                'amount' => (int) ($byDay[$day->toDateString()] ?? 0),
            ];
        });
    }

    /** Top 5 products by qty sold at this branch over the past 30 days. */
    private function topSellers(int $shopId, int $branchId, CarbonInterface $since): Collection
    {
        return SaleLine::query()
            ->join('sales', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->where('sale_lines.shop_id', $shopId)
            ->where('sale_lines.branch_id', $branchId)
            ->where('sales.sold_at', '>=', $since)
            ->where('sales.status', '!=', 'void')
            ->whereNull('sales.deleted_at')
            ->whereNull('sale_lines.deleted_at')
            ->groupBy('sale_lines.product_id', 'products.name')
            ->selectRaw(
                'sale_lines.product_id, products.name,'
                .' coalesce(sum(sale_lines.qty), 0) as qty_sold,'
                .' count(distinct sale_lines.sale_id) as sale_count,'
                .' coalesce(sum(sale_lines.line_total), 0) as revenue'
            )
            ->orderByDesc('qty_sold')
            ->limit(5)
            ->get();
    }

    /**
     * Top saved customers by spend (excludes walk-ins / null customer_id).
     * Shows past-30-day and past-1-year totals; ranked by year spend.
     */
    private function valuedCustomers(
        int $shopId,
        int $branchId,
        CarbonInterface $days30,
        CarbonInterface $yearStart,
    ): Collection {
        $yearAmounts = Sale::query()
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereNotNull('customer_id')
            ->where('sold_at', '>=', $yearStart)
            ->where('status', '!=', 'void')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, coalesce(sum(total), 0) as amount')
            ->pluck('amount', 'customer_id');

        if ($yearAmounts->isEmpty()) {
            return collect();
        }

        $monthAmounts = Sale::query()
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereIn('customer_id', $yearAmounts->keys())
            ->where('sold_at', '>=', $days30)
            ->where('status', '!=', 'void')
            ->groupBy('customer_id')
            ->selectRaw('customer_id, coalesce(sum(total), 0) as amount')
            ->pluck('amount', 'customer_id');

        $topIds = $yearAmounts->sortDesc()->keys()->take(10);

        return Customer::query()
            ->whereIn('id', $topIds)
            ->get(['id', 'name', 'phone'])
            ->map(function (Customer $customer) use ($yearAmounts, $monthAmounts) {
                $customer->setAttribute('amount_30d', (int) ($monthAmounts[$customer->id] ?? 0));
                $customer->setAttribute('amount_year', (int) ($yearAmounts[$customer->id] ?? 0));

                return $customer;
            })
            ->sortByDesc('amount_year')
            ->values();
    }
}
