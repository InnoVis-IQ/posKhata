<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\PurchaseInvoice;
use App\Models\Sale;
use App\Models\Vendor;
use App\Models\VendorPaymentAllocation;
use App\Services\VendorPayablesService;
use App\Support\PosContext;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public function __construct(private readonly VendorPayablesService $payables) {}

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    /** Sales rung up at the current branch on the given day (default: today). */
    public function daySales(Request $request): View
    {
        $data = $request->validate(['date' => ['nullable', 'date']]);
        $date = ! empty($data['date']) ? date('Y-m-d', strtotime($data['date'])) : now()->toDateString();

        $sales = Sale::query()
            ->where('shop_id', PosContext::shopId())
            ->where('branch_id', PosContext::branchId())
            ->whereDate('sold_at', $date)
            ->orderBy('sold_at')
            ->get();

        return view('reports.day-sales', ['date' => $date, 'sales' => $sales]);
    }

    /**
     * Per vendor: unpaid = invoices - allocations, unallocated credit = payments - allocations,
     * owed = VendorPayablesService::vendorOwed (invoices - all payments & credit notes).
     */
    public function payables(): View
    {
        $rows = Vendor::query()
            ->where('shop_id', PosContext::shopId())
            ->orderBy('name')
            ->get()
            ->map(function (Vendor $vendor) {
                $invoiced = (int) PurchaseInvoice::query()->where('vendor_id', $vendor->id)->sum('total');
                $allocated = (int) VendorPaymentAllocation::query()
                    ->whereIn('purchase_invoice_id', PurchaseInvoice::query()->where('vendor_id', $vendor->id)->select('id'))
                    ->sum('amount');

                return (object) [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'unpaid' => $invoiced - $allocated,
                    'unallocated_credit' => $this->payables->vendorCredit($vendor),
                    'owed' => $this->payables->vendorOwed($vendor),
                ];
            })
            ->filter(fn ($r) => $r->unpaid !== 0 || $r->unallocated_credit !== 0 || $r->owed !== 0)
            ->values();

        return view('reports.payables', ['vendors' => $rows]);
    }
}
