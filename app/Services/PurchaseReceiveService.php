<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReceiving;
use App\Models\PurchaseReceivingLine;
use App\Support\PosContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PurchaseReceiveService
{
    public function __construct(private readonly StockLedgerService $stock) {}

    /**
     * @param  array<int, array{product_id:int, qty:int|float|string, unit_cost?:int}>  $lines
     *         unit_cost defaults to the cost on the invoice line for that product.
     *
     * Received qty per product may not exceed the invoiced qty (across all receivings).
     * Stock goes to the invoice's branch. product.unit_cost is set to the last received cost.
     */
    public function receive(
        PurchaseInvoice $invoice,
        array $lines,
        ?string $notes = null,
        CarbonInterface|string|null $receivedAt = null,
    ): PurchaseReceiving {
        if ($lines === []) {
            throw new \InvalidArgumentException('Nothing to receive: no lines supplied.');
        }

        $user = PosContext::user();
        $shopId = PosContext::shopId();

        if ((int) $invoice->shop_id !== $shopId) {
            throw new \InvalidArgumentException("Purchase invoice #{$invoice->id} does not belong to this shop.");
        }

        return DB::transaction(function () use ($invoice, $lines, $notes, $receivedAt, $user, $shopId) {
            $invoice = PurchaseInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            // Aggregate lines by product so the over-receive check sees the total requested.
            $requested = [];
            foreach ($lines as $i => $line) {
                $pid = (int) ($line['product_id'] ?? 0);
                $qty = round((float) ($line['qty'] ?? 0), 3);
                if ($pid <= 0 || $qty <= 0) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': product_id and a positive qty are required.');
                }
                $requested[] = [
                    'product_id' => $pid,
                    'qty' => $qty,
                    'unit_cost' => isset($line['unit_cost']) ? (int) $line['unit_cost'] : null,
                ];
            }

            $perProduct = [];
            foreach ($requested as $r) {
                $perProduct[$r['product_id']] = ($perProduct[$r['product_id']] ?? 0) + $r['qty'];
            }

            $invoiceLines = $invoice->lines()->get()->groupBy('product_id');
            foreach ($perProduct as $pid => $qty) {
                $ordered = round((float) $invoiceLines->get($pid, collect())->sum('qty'), 3);
                if ($ordered <= 0) {
                    throw new \InvalidArgumentException("Product #{$pid} is not on invoice {$invoice->invoice_number}.");
                }
                $already = round((float) PurchaseReceivingLine::query()
                    ->whereIn('purchase_receiving_id', PurchaseReceiving::query()
                        ->where('purchase_invoice_id', $invoice->id)->select('id'))
                    ->where('product_id', $pid)
                    ->sum('qty'), 3);

                if ($already + $qty > $ordered + 0.0005) {
                    throw new \RuntimeException(
                        "Cannot receive {$qty} of product #{$pid}: invoiced {$ordered}, already received {$already}."
                    );
                }
            }

            $products = Product::query()->where('shop_id', $shopId)->whereIn('id', array_keys($perProduct))->get()->keyBy('id');

            $receiving = PurchaseReceiving::create([
                'shop_id' => $shopId,
                'branch_id' => $invoice->branch_id,
                'purchase_invoice_id' => $invoice->id,
                'received_at' => $receivedAt ?? now(),
                'created_by' => $user->id,
                'notes' => $notes,
            ]);

            foreach ($requested as $r) {
                $product = $products->get($r['product_id']);
                if ($product === null) {
                    throw new \InvalidArgumentException("Product #{$r['product_id']} not found in this shop.");
                }

                $cost = $r['unit_cost'] ?? (int) $invoiceLines->get($product->id)->first()->unit_cost;
                if ($cost < 0) {
                    throw new \InvalidArgumentException('unit_cost cannot be negative.');
                }

                $receiving->lines()->create([
                    'shop_id' => $shopId,
                    'branch_id' => $invoice->branch_id,
                    'product_id' => $product->id,
                    'qty' => number_format($r['qty'], 3, '.', ''),
                    'unit_cost' => $cost,
                ]);

                $this->stock->purchaseIn($product, $r['qty'], $receiving);

                // Last received cost wins (lines processed in order).
                $product->unit_cost = $cost;
                $product->save();
            }

            return $receiving->load('lines');
        });
    }
}
