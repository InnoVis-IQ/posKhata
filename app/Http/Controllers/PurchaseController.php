<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReceiving;
use App\Models\PurchaseReceivingLine;
use App\Models\Vendor;
use App\Services\PurchaseReceiveService;
use App\Services\VendorPayablesService;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public function __construct(
        private readonly VendorPayablesService $payables,
        private readonly PurchaseReceiveService $receiving,
    ) {}

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function index(): View
    {
        $invoices = PurchaseInvoice::query()
            ->where('shop_id', PosContext::shopId())
            ->where('branch_id', PosContext::branchId())
            ->with('vendor')
            ->latest('invoice_date')
            ->latest('id')
            ->limit(300)
            ->get()
            ->each(fn (PurchaseInvoice $i) => $i->setAttribute('vendor_name', $i->vendor?->name));

        return view('purchases.index', ['invoices' => $invoices]);
    }

    public function create(): View
    {
        return view('purchases.form', [
            'vendors' => Vendor::query()
                ->where('shop_id', PosContext::shopId())
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /** Creates the invoice + lines atomically at the current branch, then applies vendor credit to it. */
    public function store(Request $request): RedirectResponse
    {
        $shopId = PosContext::shopId();

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')->where('shop_id', $shopId)],
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('shop_id', $shopId)],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $applied = 0;

            $invoice = DB::transaction(function () use ($data, $shopId, &$applied) {
                $exists = PurchaseInvoice::query()
                    ->where('shop_id', $shopId)
                    ->where('vendor_id', $data['vendor_id'])
                    ->where('invoice_number', $data['invoice_number'])
                    ->exists();
                if ($exists) {
                    throw new \InvalidArgumentException("Invoice {$data['invoice_number']} already exists for this vendor.");
                }

                $branchId = PosContext::branchId();
                $rows = [];
                $subtotal = 0;
                foreach ($data['lines'] as $line) {
                    $qty = round((float) $line['qty'], 3);
                    $cost = (int) $line['unit_cost'];
                    $lineTotal = (int) round($qty * $cost);
                    $rows[] = [
                        'product_id' => (int) $line['product_id'],
                        'qty' => number_format($qty, 3, '.', ''),
                        'unit_cost' => $cost,
                        'tax_amount' => 0,
                        'line_total' => $lineTotal,
                    ];
                    $subtotal += $lineTotal;
                }

                $invoice = PurchaseInvoice::create([
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'vendor_id' => (int) $data['vendor_id'],
                    'invoice_number' => $data['invoice_number'],
                    'invoice_date' => $data['invoice_date'],
                    'due_date' => $data['due_date'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_total' => 0,
                    'total' => $subtotal,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => PosContext::user()->id,
                ]);

                foreach ($rows as $row) {
                    $invoice->lines()->create($row + ['shop_id' => $shopId, 'branch_id' => $branchId]);
                }

                $applied = $this->payables->applyUnallocatedCreditsToInvoice($invoice);

                return $invoice;
            });
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        $message = "Invoice {$invoice->invoice_number} saved.";
        if ($applied > 0) {
            $message .= " Vendor credit of {$applied} (minor units) was applied to it.";
        }

        return redirect()->route('purchases.index')->with('status', $message);
    }

    public function receive(int $purchase): View|RedirectResponse
    {
        $invoice = $this->find($purchase, ['vendor', 'lines.product']);
        $remaining = $this->remainingByProduct($invoice);

        // One row per product (aggregated over duplicate invoice lines), only what is still to receive.
        $lines = $invoice->lines->groupBy('product_id')->map(function ($group, $productId) use ($remaining) {
            $left = $remaining[$productId] ?? 0;
            if ($left <= 0) {
                return null;
            }
            $first = $group->first();

            return (object) [
                'product_id' => (int) $productId,
                'product_name' => $first->product?->name,
                'qty' => $left,
                'unit_cost' => (int) $first->unit_cost,
            ];
        })->filter()->values();

        if ($lines->isEmpty()) {
            return redirect()->route('purchases.index')
                ->with('warning', "Invoice {$invoice->invoice_number} is already fully received.");
        }

        $invoice->setAttribute('vendor_name', $invoice->vendor?->name);
        $invoice->setRelation('lines', $lines);

        return view('purchases.receive', ['invoice' => $invoice]);
    }

    public function storeReceive(Request $request, int $purchase): RedirectResponse
    {
        $invoice = $this->find($purchase);

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.qty' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);

        $lines = collect($data['lines'])
            ->filter(fn ($l) => (float) ($l['qty'] ?? 0) > 0)
            ->map(fn ($l) => array_filter([
                'product_id' => (int) $l['product_id'],
                'qty' => $l['qty'],
                'unit_cost' => isset($l['unit_cost']) ? (int) $l['unit_cost'] : null,
            ], fn ($v) => $v !== null))
            ->values()
            ->all();

        try {
            $this->receiving->receive($invoice, $lines);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('purchases.index')->with('status', "Stock received for invoice {$invoice->invoice_number}.");
    }

    private function find(int $id, array $with = []): PurchaseInvoice
    {
        return PurchaseInvoice::query()
            ->where('shop_id', PosContext::shopId())
            ->with($with)
            ->findOrFail($id);
    }

    /** @return array<int, float> product_id => qty invoiced minus qty already received */
    private function remainingByProduct(PurchaseInvoice $invoice): array
    {
        $received = PurchaseReceivingLine::query()
            ->whereIn('purchase_receiving_id', PurchaseReceiving::query()
                ->where('purchase_invoice_id', $invoice->id)->select('id'))
            ->selectRaw('product_id, SUM(qty) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $out = [];
        foreach ($invoice->lines->groupBy('product_id') as $productId => $group) {
            $out[$productId] = round((float) $group->sum('qty') - (float) ($received[$productId] ?? 0), 3);
        }

        return $out;
    }
}
