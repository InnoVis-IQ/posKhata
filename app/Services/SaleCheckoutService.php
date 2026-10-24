<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\KhaataEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Support\NumberSequence;
use App\Support\PosContext;
use Illuminate\Support\Facades\DB;

class SaleCheckoutService
{
    public function __construct(
        private readonly RegisterService $register,
        private readonly StockLedgerService $stock,
        private readonly KhaataPaymentService $khaataPayments,
    ) {}

    /**
     * @param  array{
     *   lines: array<int, array{product_id:int, qty:int|float|string, unit_price?:int, discount_amount?:int}>,
     *   payments?: array<int, array{method:string, amount:int}>,
     *   customer_id?: int|null,
     *   customer?: array{name?:string, phone?:?string, email?:?string, address?:?string},
     *   save_customer?: bool,
     *   khaata_payment?: array{amount:int, method:string, receipt_number?:?string, note?:?string},
     *   notes?: ?string,
     *   sold_at?: mixed
     * }  $data  All money is integer minor units. unit_price / discount_amount on a line are optional overrides
     *           (discount_amount = TOTAL line discount). Method "khaata" is store credit tender.
     *
     * Sale amounts: line_total is tax-inclusive (gross - discount); tax_amount is the tax portion included in it;
     * sale.subtotal = SUM(line_total - tax_amount); sale.total = subtotal + tax_total = SUM(line_total).
     * discount_total is informational (already deducted from line_total).
     */
    public function checkout(array $data): Sale
    {
        $user = PosContext::user();
        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();

        $rawLines = $data['lines'] ?? [];
        if (! is_array($rawLines) || $rawLines === []) {
            throw new \InvalidArgumentException('A sale needs at least one line.');
        }

        return DB::transaction(function () use ($data, $rawLines, $user, $shopId, $branchId) {
            $session = $this->register->requireOpenSession($branchId);

            $customer = $this->resolveCustomer($data, $shopId);

            // ---- lines ---------------------------------------------------------------
            $products = Product::query()
                ->where('shop_id', $shopId)
                ->whereIn('id', collect($rawLines)->pluck('product_id')->filter()->unique()->all())
                ->get()
                ->keyBy('id');

            $computed = [];
            $subtotal = $discountTotal = $taxTotal = $total = 0;

            foreach ($rawLines as $i => $line) {
                $product = $products->get((int) ($line['product_id'] ?? 0));
                if ($product === null) {
                    throw new \InvalidArgumentException('Line '.($i + 1).": product #".($line['product_id'] ?? '?').' not found in this shop.');
                }
                if (! $product->is_active) {
                    throw new \RuntimeException("Product '{$product->name}' is inactive and cannot be sold.");
                }

                $qty = round((float) ($line['qty'] ?? 0), 3);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException("Line ".($i + 1).": quantity must be greater than zero.");
                }

                $unitPrice = isset($line['unit_price']) ? (int) $line['unit_price'] : (int) $product->unit_sale_price;
                $gross = (int) round($qty * $unitPrice);

                if (isset($line['discount_amount'])) {
                    $discount = (int) $line['discount_amount'];
                } else {
                    $discount = (int) round($qty * (int) $product->unit_discount);
                    $batchSize = (float) $product->batch_size;
                    if ($batchSize > 0 && (int) $product->batch_discount > 0) {
                        $discount += (int) floor($qty / $batchSize + 1e-9) * (int) $product->batch_discount;
                    }
                }
                if ($discount < 0 || $discount > $gross) {
                    throw new \InvalidArgumentException("Line ".($i + 1).": discount must be between 0 and the line amount.");
                }

                $lineTotal = $gross - $discount;
                $tax = $gross > 0 ? (int) round($qty * (int) $product->tax * ($lineTotal / $gross)) : 0;
                $tax = min($tax, $lineTotal);

                $computed[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => (int) $product->unit_cost,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'line_total' => $lineTotal,
                ];

                $subtotal += $lineTotal - $tax;
                $discountTotal += $discount;
                $taxTotal += $tax;
                $total += $lineTotal;
            }

            // ---- payments ------------------------------------------------------------
            $payments = $this->normalisePayments($data['payments'] ?? [], $total);
            $khaataTender = (int) collect($payments)->where('method', 'khaata')->sum('amount');
            if ($khaataTender > 0 && $customer === null) {
                throw new \InvalidArgumentException('A customer is required to put a sale on khaata.');
            }

            // ---- sale ----------------------------------------------------------------
            $soldAt = $data['sold_at'] ?? now();
            $prefix = 'S-'.now()->format('Ymd').'-';

            $sale = Sale::create([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'customer_id' => $customer?->id,
                'register_session_id' => $session->id,
                'sale_number' => NumberSequence::next($shopId, 'sales', 'sale_number', $prefix),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'sold_at' => $soldAt,
                'cashier_id' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($computed as $c) {
                $sale->lines()->create([
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'product_id' => $c['product']->id,
                    'qty' => number_format($c['qty'], 3, '.', ''),
                    'unit_price' => $c['unit_price'],
                    'unit_cost' => $c['unit_cost'],
                    'discount_amount' => $c['discount_amount'],
                    'tax_amount' => $c['tax_amount'],
                    'line_total' => $c['line_total'],
                ]);

                $this->stock->saleOut($c['product'], $c['qty'], $sale);
            }

            foreach ($payments as $p) {
                $sale->payments()->create([
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'method' => $p['method'],
                    'amount' => $p['amount'],
                ]);
            }

            if ($khaataTender > 0) {
                KhaataEntry::create([
                    'shop_id' => $shopId,
                    'branch_id' => $branchId,
                    'customer_id' => $customer->id,
                    'amount_delta' => $khaataTender,
                    'entry_type' => 'sale',
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'note' => "Sale {$sale->sale_number}",
                    'created_by' => $user->id,
                ]);
            }

            // ---- optional khaata pay-down (customer pays old balance during checkout) --
            $paydown = $data['khaata_payment'] ?? null;
            if (is_array($paydown) && (int) ($paydown['amount'] ?? 0) > 0) {
                if ($customer === null) {
                    throw new \InvalidArgumentException('A customer is required to pay down khaata.');
                }
                $this->khaataPayments->record(
                    $customer,
                    (int) $paydown['amount'],
                    (string) ($paydown['method'] ?? 'cash'),
                    $paydown['receipt_number'] ?? null,
                    $soldAt,
                    $paydown['note'] ?? "Paid with sale {$sale->sale_number}",
                    $session->id,
                );
            }

            return $sale->load(['lines', 'payments']);
        });
    }

    private function resolveCustomer(array $data, int $shopId): ?Customer
    {
        if (! empty($data['customer_id'])) {
            $customer = Customer::query()->whereKey($data['customer_id'])->where('shop_id', $shopId)->first();
            if ($customer === null) {
                throw new \InvalidArgumentException("Customer #{$data['customer_id']} not found in this shop.");
            }

            return $customer;
        }

        $info = $data['customer'] ?? null;
        if (! empty($data['save_customer']) && is_array($info)) {
            $name = trim((string) ($info['name'] ?? ''));
            $phone = isset($info['phone']) ? trim((string) $info['phone']) : null;
            $phone = $phone === '' ? null : $phone;

            if ($phone !== null) {
                $existing = Customer::query()->where('shop_id', $shopId)->where('phone', $phone)->first();
                if ($existing !== null) {
                    return $existing;
                }
            }

            if ($name === '') {
                throw new \InvalidArgumentException('Customer name is required to save a customer.');
            }

            return Customer::create([
                'shop_id' => $shopId,
                'name' => $name,
                'phone' => $phone,
                'email' => $info['email'] ?? null,
                'address' => $info['address'] ?? null,
                'is_active' => true,
            ]);
        }

        return null;
    }

    /**
     * Validates tenders, removes cash change (overpayment must be covered by cash), and returns
     * rows that sum exactly to $total.
     *
     * @return array<int, array{method:string, amount:int}>
     */
    private function normalisePayments(mixed $raw, int $total): array
    {
        $rows = [];
        foreach ((array) $raw as $p) {
            $method = strtolower(trim((string) ($p['method'] ?? '')));
            $amount = (int) round((float) ($p['amount'] ?? 0));
            if ($method === '') {
                throw new \InvalidArgumentException('Each payment needs a method.');
            }
            if ($amount <= 0) {
                throw new \InvalidArgumentException("Payment amount for '{$method}' must be greater than zero.");
            }
            $rows[] = ['method' => $method, 'amount' => $amount];
        }

        $paid = array_sum(array_column($rows, 'amount'));

        if ($paid < $total) {
            throw new \InvalidArgumentException("Payments ({$paid}) do not cover the sale total ({$total}).");
        }

        $change = $paid - $total;
        if ($change > 0) {
            for ($i = count($rows) - 1; $i >= 0 && $change > 0; $i--) {
                if ($rows[$i]['method'] !== 'cash') {
                    continue;
                }
                $take = min($change, $rows[$i]['amount']);
                $rows[$i]['amount'] -= $take;
                $change -= $take;
            }
            if ($change > 0) {
                throw new \InvalidArgumentException('Overpayment can only be returned as cash change.');
            }
            $rows = array_values(array_filter($rows, fn ($r) => $r['amount'] > 0));
        }

        return $rows;
    }
}
