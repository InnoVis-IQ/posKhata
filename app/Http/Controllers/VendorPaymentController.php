<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\PurchaseInvoice;
use App\Models\Vendor;
use App\Services\VendorPayablesService;
use App\Support\Money;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorPaymentController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public function __construct(private readonly VendorPayablesService $payables) {}

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function create(): View
    {
        $shopId = PosContext::shopId();

        $invoices = PurchaseInvoice::query()
            ->where('shop_id', $shopId)
            ->with('vendor')
            ->withSum('paymentAllocations as allocated_sum', 'amount')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (PurchaseInvoice $i) => Money::round((float) $i->total - (float) $i->allocated_sum) > 0)
            ->each(function (PurchaseInvoice $i) {
                $i->setAttribute('owed', Money::round((float) $i->total - (float) $i->allocated_sum));
                $i->setAttribute('vendor_name', $i->vendor?->name);
            })
            ->values();

        return view('vendor-payments.form', [
            'vendors' => Vendor::query()->where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get(),
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = PosContext::shopId();

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')->where('shop_id', $shopId)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(['cash', 'bank', 'card', 'other'])],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.purchase_invoice_id' => ['nullable', 'integer'],
            'allocations.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Default is FIFO (null); only honour the allocation rows when the override box is ticked.
        $allocations = null;
        if ($request->boolean('manual_allocation')) {
            $allocations = collect($data['allocations'] ?? [])
                ->filter(fn ($row) => ! empty($row['purchase_invoice_id']) && Money::round((float) ($row['amount'] ?? 0)) > 0)
                ->values()
                ->all();

            if ($allocations === []) {
                return $this->backWithError(new \InvalidArgumentException(
                    'Manual allocation is on but no invoice/amount rows were filled in.'
                ));
            }
        }

        try {
            $vendor = Vendor::query()->where('shop_id', $shopId)->findOrFail((int) $data['vendor_id']);
            $payment = $this->payables->pay(
                $vendor,
                Money::round($data['amount']),
                $data['method'],
                $allocations,
                $data['note'] ?? null,
                $data['paid_at'] ?? null,
            );
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        $allocated = Money::round($payment->allocations->sum('amount'));
        $message = "Payment of {$payment->amount} to {$vendor->name} recorded ({$allocated} allocated to invoices";
        $message .= $allocated < Money::round((float) $payment->amount) ? '; the rest is held as vendor credit).' : ').';

        return redirect()->route('purchases.index')->with('status', $message);
    }
}
