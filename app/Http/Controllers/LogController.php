<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\KhaataPayment;
use App\Models\Product;
use App\Models\RegisterCashMovement;
use App\Models\SalePayment;
use App\Models\SaleRefundPayment;
use App\Models\StockLedgerEntry;
use App\Models\VendorPayment;
use App\Support\PosContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Read-only logs over append-only stock ledger + money in/out sources.
 */
class LogController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function stock(Request $request): View
    {
        $data = $request->validate([
            'direction' => ['nullable', 'in:in,out'],
            'movement_type' => ['nullable', 'string', 'max:30'],
            'product_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        [$from, $to] = $this->dateRange($request);

        $query = StockLedgerEntry::query()
            ->with(['product', 'creator', 'branch'])
            ->where('shop_id', PosContext::shopId())
            ->where('branch_id', PosContext::branchId())
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (! empty($data['movement_type'])) {
            $query->where('movement_type', $data['movement_type']);
        }

        if (! empty($data['product_id'])) {
            $query->where('product_id', (int) $data['product_id']);
        }

        if (($data['direction'] ?? null) === 'in') {
            $query->where('qty_delta', '>', 0);
        } elseif (($data['direction'] ?? null) === 'out') {
            $query->where('qty_delta', '<', 0);
        }

        $entries = $query->paginate(50)->withQueryString();

        $products = Product::query()
            ->where('shop_id', PosContext::shopId())
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $movementTypes = StockLedgerEntry::query()
            ->where('shop_id', PosContext::shopId())
            ->distinct()
            ->orderBy('movement_type')
            ->pluck('movement_type');

        return view('logs.stock', [
            'entries' => $entries,
            'products' => $products,
            'movementTypes' => $movementTypes,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'filters' => $data,
        ]);
    }

    public function payments(Request $request): View
    {
        $data = $request->validate([
            'direction' => ['nullable', 'in:in,out'],
            'kind' => ['nullable', 'in:sale,khaata,vendor,refund,register'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        [$from, $to] = $this->dateRange($request);

        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();
        $minorUnits = (int) (PosContext::shop()->currency_minor_units ?? 2);

        $rows = collect()
            ->merge($this->salePaymentRows($shopId, $branchId, $start, $end))
            ->merge($this->khaataPaymentRows($shopId, $branchId, $start, $end))
            ->merge($this->vendorPaymentRows($shopId, $branchId, $start, $end))
            ->merge($this->refundPaymentRows($shopId, $branchId, $start, $end))
            ->merge($this->registerMovementRows($shopId, $branchId, $start, $end));

        if (($data['direction'] ?? null) === 'in') {
            $rows = $rows->where('direction', 'in');
        } elseif (($data['direction'] ?? null) === 'out') {
            $rows = $rows->where('direction', 'out');
        }

        if (! empty($data['kind'])) {
            $rows = $rows->where('kind', $data['kind']);
        }

        $sorted = $rows->sortByDesc(fn ($row) => $row->when?->getTimestamp() ?? 0)->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 50;
        $pageItems = $sorted->forPage($page, $perPage)->values();

        $entries = new LengthAwarePaginator($pageItems, $sorted->count(), $perPage, $page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('logs.payments', [
            'entries' => $entries,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'filters' => $data,
            'minorUnits' => $minorUnits,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function dateRange(Request $request): array
    {
        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))
            : now()->subDays(6)->startOfDay();
        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))
            : now()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /** @return Collection<int, object> */
    private function salePaymentRows(int $shopId, int $branchId, Carbon $start, Carbon $end): Collection
    {
        return SalePayment::query()
            ->with('sale:id,sale_number')
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(fn (SalePayment $p) => (object) [
                'when' => $p->created_at,
                'direction' => 'in',
                'kind' => 'sale',
                'party' => $p->sale?->sale_number ?? ('sale #'.$p->sale_id),
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'note' => null,
            ]);
    }

    /** @return Collection<int, object> */
    private function khaataPaymentRows(int $shopId, int $branchId, Carbon $start, Carbon $end): Collection
    {
        return KhaataPayment::query()
            ->with('customer:id,name')
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereBetween('received_at', [$start, $end])
            ->get()
            ->map(fn (KhaataPayment $p) => (object) [
                'when' => $p->received_at ?? $p->created_at,
                'direction' => 'in',
                'kind' => 'khaata',
                'party' => $p->customer?->name ?? ('customer #'.$p->customer_id),
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'note' => trim(($p->receipt_number ? $p->receipt_number.' · ' : '').($p->note ?? ''), ' ·'),
            ]);
    }

    /** @return Collection<int, object> */
    private function vendorPaymentRows(int $shopId, int $branchId, Carbon $start, Carbon $end): Collection
    {
        return VendorPayment::query()
            ->with('vendor:id,name')
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereBetween('paid_at', [$start, $end])
            ->get()
            ->map(fn (VendorPayment $p) => (object) [
                'when' => $p->paid_at ?? $p->created_at,
                'direction' => 'out',
                'kind' => 'vendor',
                'party' => $p->vendor?->name ?? ('vendor #'.$p->vendor_id),
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'note' => $p->note,
            ]);
    }

    /** @return Collection<int, object> */
    private function refundPaymentRows(int $shopId, int $branchId, Carbon $start, Carbon $end): Collection
    {
        return SaleRefundPayment::query()
            ->with(['saleRefund.sale:id,sale_number'])
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(fn (SaleRefundPayment $p) => (object) [
                'when' => $p->created_at,
                'direction' => 'out',
                'kind' => 'refund',
                'party' => $p->saleRefund?->sale?->sale_number
                    ?? ('refund #'.$p->sale_refund_id),
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'note' => $p->saleRefund?->kind,
            ]);
    }

    /** @return Collection<int, object> */
    private function registerMovementRows(int $shopId, int $branchId, Carbon $start, Carbon $end): Collection
    {
        return RegisterCashMovement::query()
            ->where('shop_id', $shopId)
            ->where('branch_id', $branchId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(fn (RegisterCashMovement $m) => (object) [
                'when' => $m->created_at,
                'direction' => $m->direction === 'in' ? 'in' : 'out',
                'kind' => 'register',
                'party' => 'Register',
                'method' => $m->reason,
                'amount' => (float) $m->amount,
                'note' => $m->note,
            ]);
    }
}
