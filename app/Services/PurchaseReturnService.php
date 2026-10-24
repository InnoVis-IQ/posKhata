<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\Vendor;
use App\Support\PosContext;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private readonly StockLedgerService $stock,
        private readonly VendorPayablesService $payables,
    ) {}

    /**
     * @param  array{
     *   vendor_id?: int,
     *   purchase_invoice_id?: ?int,
     *   return_date?: mixed,
     *   notes?: ?string,
     *   lines: array<int, array{product_id:int, qty:int|float|string, unit_cost?:int}>
     * }  $data  vendor_id is optional when purchase_invoice_id is given. Stock leaves the invoice's
     *           branch (or the current branch when there is no invoice). unit_cost defaults to the
     *           invoice line cost (or product.unit_cost). line_total = qty * unit_cost (ex-tax);
     *           tax_total is pro-rated from the invoice line tax; total = subtotal + tax_total.
     */
    public function create(array $data): PurchaseReturn
    {
        $lines = $data['lines'] ?? [];
        if (! is_array($lines) || $lines === []) {
            throw new \InvalidArgumentException('A purchase return needs at least one line.');
        }

        $user = PosContext::user();
        $shopId = PosContext::shopId();

        return DB::transaction(function () use ($data, $lines, $user, $shopId) {
            $invoice = null;
            if (! empty($data['purchase_invoice_id'])) {
                $invoice = PurchaseInvoice::query()
                    ->whereKey($data['purchase_invoice_id'])
                    ->where('shop_id', $shopId)
                    ->lockForUpdate()
                    ->first();
                if ($invoice === null) {
                    throw new \InvalidArgumentException("Purchase invoice #{$data['purchase_invoice_id']} not found in this shop.");
                }
            }

            $vendorId = (int) ($data['vendor_id'] ?? $invoice?->vendor_id ?? 0);
            if ($vendorId <= 0) {
                throw new \InvalidArgumentException('vendor_id or purchase_invoice_id is required.');
            }
            if ($invoice !== null && (int) $invoice->vendor_id !== $vendorId) {
                throw new \InvalidArgumentException('Invoice does not belong to the selected vendor.');
            }
            Vendor::query()->whereKey($vendorId)->where('shop_id', $shopId)->firstOrFail();

            $branchId = (int) ($invoice?->branch_id ?? PosContext::branchId());

            $invoiceLines = $invoice ? $invoice->lines()->get()->groupBy('product_id') : collect();
            $products = Product::query()
                ->where('shop_id', $shopId)
                ->whereIn('id', collect($lines)->pluck('product_id')->filter()->unique()->all())
                ->get()->keyBy('id');

            // Prevent returning more than was invoiced (net of earlier returns).
            $perProduct = [];
            foreach ($lines as $line) {
                $pid = (int) ($line['product_id'] ?? 0);
                $perProduct[$pid] = ($perProduct[$pid] ?? 0) + round((float) ($line['qty'] ?? 0), 3);
            }
            if ($invoice !== null) {
                foreach ($perProduct as $pid => $qty) {
                    $invoiced = round((float) $invoiceLines->get($pid, collect())->sum('qty'), 3);
                    if ($invoiced <= 0) {
                        throw new \InvalidArgumentException("Product #{$pid} is not on invoice {$invoice->invoice_number}.");
                    }
                    $returned = round((float) PurchaseReturnLine::query()
                        ->whereIn('purchase_return_id', PurchaseReturn::query()
                            ->where('purchase_invoice_id', $invoice->id)->select('id'))
                        ->where('product_id', $pid)->sum('qty'), 3);
                    if ($returned + $qty > $invoiced + 0.0005) {
                        throw new \RuntimeException(
                            "Cannot return {$qty} of product #{$pid}: invoiced {$invoiced}, already returned {$returned}."
                        );
                    }
                }
            }

            $computed = [];
            $subtotal = $taxTotal = 0;
            foreach ($lines as $i => $line) {
                $product = $products->get((int) ($line['product_id'] ?? 0));
                if ($product === null) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': product not found in this shop.');
                }
                $qty = round((float) ($line['qty'] ?? 0), 3);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': qty must be greater than zero.');
                }

                $invLine = $invoiceLines->get($product->id)?->first();
                $unitCost = isset($line['unit_cost'])
                    ? (int) $line['unit_cost']
                    : (int) ($invLine?->unit_cost ?? $product->unit_cost);
                if ($unitCost < 0) {
                    throw new \InvalidArgumentException('unit_cost cannot be negative.');
                }

                $lineTotal = (int) round($qty * $unitCost);
                $tax = 0;
                if ($invLine !== null && (float) $invLine->qty > 0) {
                    $tax = (int) round((int) $invLine->tax_amount * ($qty / (float) $invLine->qty));
                }

                $computed[] = compact('product', 'qty', 'unitCost', 'lineTotal');
                $subtotal += $lineTotal;
                $taxTotal += $tax;
            }

            $return = PurchaseReturn::create([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'vendor_id' => $vendorId,
                'purchase_invoice_id' => $invoice?->id,
                'return_date' => $data['return_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($computed as $c) {
                $return->lines()->create([
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'product_id' => $c['product']->id,
                    'qty' => number_format($c['qty'], 3, '.', ''),
                    'unit_cost' => $c['unitCost'],
                    'line_total' => $c['lineTotal'],
                ]);

                $this->stock->purchaseReturn($c['product'], $c['qty'], $return);
            }

            $this->payables->creditNoteFromReturn($return);

            return $return->load('lines');
        });
    }
}
