<?php

namespace App\Services;

use App\Models\KhaataEntry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use App\Models\SaleRefund;
use App\Models\SaleRefundLine;
use App\Models\SaleRefundPayment;
use App\Support\PosContext;
use Illuminate\Support\Facades\DB;

class SaleRefundVoidService
{
    public const KIND_REFUND = 'refund';

    public const KIND_VOID = 'void';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        private readonly RegisterService $register,
        private readonly StockLedgerService $stock,
    ) {}

    /**
     * Create a pending refund / void request.
     *
     * @param  array{
     *   kind: 'refund'|'void',
     *   reason: string,
     *   restock?: bool,
     *   lines?: array<int, array{sale_line_id:int, qty:int|float|string}>
     * }  $data  For kind=void, lines are ignored: the whole remaining sale is voided (sale must be
     *           'completed' with no pending requests). Refund amounts are the pro-rata share of
     *           sale_lines.line_total (tax-inclusive); refund.subtotal = total - tax_total.
     */
    public function request(Sale $sale, array $data): SaleRefund
    {
        $kind = $data['kind'] ?? self::KIND_REFUND;
        if (! in_array($kind, [self::KIND_REFUND, self::KIND_VOID], true)) {
            throw new \InvalidArgumentException("kind must be 'refund' or 'void'.");
        }
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            throw new \InvalidArgumentException('A reason is required.');
        }

        $user = PosContext::user();
        if ((int) $sale->shop_id !== PosContext::shopId()) {
            throw new \InvalidArgumentException("Sale #{$sale->id} does not belong to this shop.");
        }

        return DB::transaction(function () use ($sale, $data, $kind, $reason, $user) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if (! in_array($sale->status, ['completed', 'partially_refunded'], true)) {
                throw new \RuntimeException("Sale {$sale->sale_number} has status '{$sale->status}' and cannot be refunded/voided.");
            }

            $saleLines = $sale->lines()->get()->keyBy('id');
            $remaining = $this->remainingQty($saleLines->all());

            if ($kind === self::KIND_VOID) {
                if ($sale->status !== 'completed') {
                    throw new \RuntimeException('Only a fully intact (completed) sale can be voided; use a refund instead.');
                }
                $pending = SaleRefund::query()->where('sale_id', $sale->id)->where('status', self::STATUS_PENDING)->exists();
                if ($pending) {
                    throw new \RuntimeException('This sale already has a pending refund/void request.');
                }
                $requested = [];
                foreach ($saleLines as $line) {
                    if ($remaining[$line->id] > 0) {
                        $requested[] = ['sale_line_id' => $line->id, 'qty' => $remaining[$line->id]];
                    }
                }
            } else {
                $requested = $data['lines'] ?? [];
            }

            if ($requested === []) {
                throw new \InvalidArgumentException('Nothing to refund: no lines with refundable quantity.');
            }

            $rows = [];
            $total = $tax = 0;
            foreach ($requested as $i => $r) {
                $line = $saleLines->get((int) ($r['sale_line_id'] ?? 0));
                if ($line === null) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': sale line not found on this sale.');
                }
                $qty = round((float) ($r['qty'] ?? 0), 3);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': qty must be greater than zero.');
                }
                if ($qty > $remaining[$line->id] + 0.0005) {
                    throw new \RuntimeException(
                        "Line ".($i + 1).": only {$remaining[$line->id]} of sale line #{$line->id} is refundable (requested {$qty})."
                    );
                }
                $remaining[$line->id] = round($remaining[$line->id] - $qty, 3);

                $ratio = $qty / (float) $line->qty;
                $amount = (int) round((int) $line->line_total * $ratio);
                $lineTax = (int) round((int) $line->tax_amount * $ratio);

                $rows[] = ['line' => $line, 'qty' => $qty, 'amount' => $amount];
                $total += $amount;
                $tax += min($lineTax, $amount);
            }

            $refund = SaleRefund::create([
                'shop_id' => $sale->shop_id,
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'kind' => $kind,
                'status' => self::STATUS_PENDING,
                'restock' => (bool) ($data['restock'] ?? true),
                'reason' => $reason,
                'requested_by' => $user->id,
                'subtotal' => $total - $tax,
                'tax_total' => $tax,
                'total' => $total,
            ]);

            foreach ($rows as $row) {
                $refund->lines()->create([
                    'shop_id' => $sale->shop_id,
                    'branch_id' => $sale->branch_id,
                    'sale_line_id' => $row['line']->id,
                    'product_id' => $row['line']->product_id,
                    'qty' => number_format($row['qty'], 3, '.', ''),
                    'amount' => $row['amount'],
                ]);
            }

            return $refund->load('lines');
        });
    }

    /**
     * Approve (admin/superadmin only). $payments: [['method'=>..,'amount'=>..]] summing to refund total;
     * null => refund back to the original tender methods (net of earlier refunds), in the order paid.
     * Cash needs an open register session at the sale's branch. "khaata" reduces the customer's balance.
     *
     * @param  array<int, array{method:string, amount:int}>|null  $payments
     */
    public function approve(SaleRefund $refund, ?array $payments = null): SaleRefund
    {
        $user = PosContext::user();
        if (! PosContext::isAdmin()) {
            throw new \RuntimeException('Only an admin can approve refunds/voids.');
        }
        if ((int) $refund->shop_id !== PosContext::shopId()) {
            throw new \InvalidArgumentException("Refund #{$refund->id} does not belong to this shop.");
        }

        return DB::transaction(function () use ($refund, $payments, $user) {
            $refund = SaleRefund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== self::STATUS_PENDING) {
                throw new \RuntimeException("Refund #{$refund->id} is already {$refund->status}.");
            }

            $sale = Sale::query()->whereKey($refund->sale_id)->lockForUpdate()->firstOrFail();
            $payments = $payments === null
                ? $this->defaultPayments($sale, $refund)
                : $this->normalisePayments($payments, (int) $refund->total);

            $needsCash = collect($payments)->contains(fn ($p) => $p['method'] === 'cash');
            $session = $needsCash ? $this->register->requireOpenSession((int) $refund->branch_id) : null;

            $khaataAmount = (int) collect($payments)->where('method', 'khaata')->sum('amount');
            if ($khaataAmount > 0 && $sale->customer_id === null) {
                throw new \RuntimeException('Cannot refund to khaata: the sale has no customer.');
            }

            foreach ($payments as $p) {
                SaleRefundPayment::create([
                    'shop_id' => $refund->shop_id,
                    'branch_id' => $refund->branch_id,
                    'sale_refund_id' => $refund->id,
                    'method' => $p['method'],
                    'amount' => $p['amount'],
                    'register_session_id' => $p['method'] === 'cash' ? $session->id : null,
                ]);
            }

            if ($khaataAmount > 0) {
                KhaataEntry::create([
                    'shop_id' => $refund->shop_id,
                    'branch_id' => $refund->branch_id,
                    'customer_id' => $sale->customer_id,
                    'amount_delta' => -$khaataAmount,
                    'entry_type' => 'refund',
                    'reference_type' => 'sale_refund',
                    'reference_id' => $refund->id,
                    'note' => "Refund for sale {$sale->sale_number}",
                    'created_by' => $user->id,
                ]);
            }

            if ($refund->restock) {
                $lines = $refund->lines()->get();
                $products = Product::query()->whereIn('id', $lines->pluck('product_id'))->get()->keyBy('id');
                foreach ($lines as $line) {
                    $this->stock->saleReturn($products->get($line->product_id), (float) $line->qty, $refund);
                }
            }

            $refund->update([
                'status' => self::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->updateSaleStatus($sale, $refund);

            return $refund->refresh()->load(['lines', 'payments']);
        });
    }

    public function reject(SaleRefund $refund, ?string $note = null): SaleRefund
    {
        $user = PosContext::user();
        if (! PosContext::isAdmin()) {
            throw new \RuntimeException('Only an admin can reject refunds/voids.');
        }
        if ((int) $refund->shop_id !== PosContext::shopId()) {
            throw new \InvalidArgumentException("Refund #{$refund->id} does not belong to this shop.");
        }

        return DB::transaction(function () use ($refund, $note, $user) {
            $refund = SaleRefund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== self::STATUS_PENDING) {
                throw new \RuntimeException("Refund #{$refund->id} is already {$refund->status}.");
            }

            // No rejected_by column: the decision is appended to the reason for traceability.
            $refund->update([
                'status' => self::STATUS_REJECTED,
                'reason' => $refund->reason."\n[Rejected by {$user->name}".($note ? ": {$note}" : '').']',
            ]);

            return $refund->refresh();
        });
    }

    // ---------------------------------------------------------------- internals

    /**
     * Refundable qty per sale line = line qty - qty on pending/approved refund lines.
     *
     * @param  array<int|string, SaleLine>  $saleLines
     * @return array<int, float>
     */
    private function remainingQty(array $saleLines): array
    {
        $out = [];
        foreach ($saleLines as $line) {
            $used = (float) SaleRefundLine::query()
                ->where('sale_line_id', $line->id)
                ->whereIn('sale_refund_id', SaleRefund::query()
                    ->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED])->select('id'))
                ->sum('qty');
            $out[$line->id] = round((float) $line->qty - $used, 3);
        }

        return $out;
    }

    /** @return array<int, array{method:string, amount:int}> */
    private function normalisePayments(array $payments, int $total): array
    {
        $rows = [];
        foreach ($payments as $p) {
            $method = strtolower(trim((string) ($p['method'] ?? '')));
            $amount = (int) round((float) ($p['amount'] ?? 0));
            if ($method === '' || $amount <= 0) {
                throw new \InvalidArgumentException('Each refund payment needs a method and a positive amount.');
            }
            $rows[] = ['method' => $method, 'amount' => $amount];
        }
        $sum = array_sum(array_column($rows, 'amount'));
        if ($sum !== $total) {
            throw new \InvalidArgumentException("Refund payments ({$sum}) must equal the refund total ({$total}).");
        }

        return $rows;
    }

    /** Refund to original tenders, net of earlier approved refunds. */
    private function defaultPayments(Sale $sale, SaleRefund $refund): array
    {
        $paid = [];
        foreach (SalePayment::query()->where('sale_id', $sale->id)->orderBy('id')->get() as $p) {
            $paid[$p->method] = ($paid[$p->method] ?? 0) + (int) $p->amount;
        }

        $refunded = SaleRefundPayment::query()
            ->whereIn('sale_refund_id', SaleRefund::query()
                ->where('sale_id', $sale->id)->where('status', self::STATUS_APPROVED)->select('id'))
            ->selectRaw('method, SUM(amount) as total')
            ->groupBy('method')
            ->pluck('total', 'method');

        $left = (int) $refund->total;
        $rows = [];
        foreach ($paid as $method => $amount) {
            if ($left <= 0) {
                break;
            }
            $available = $amount - (int) ($refunded[$method] ?? 0);
            $take = min($left, $available);
            if ($take > 0) {
                $rows[] = ['method' => $method, 'amount' => $take];
                $left -= $take;
            }
        }

        if ($left > 0) {
            throw new \RuntimeException('Refund total exceeds the amount originally paid for this sale.');
        }

        return $rows;
    }

    private function updateSaleStatus(Sale $sale, SaleRefund $refund): void
    {
        if ($refund->kind === self::KIND_VOID) {
            $sale->update(['status' => 'void']);

            return;
        }

        $approvedQty = (float) SaleRefundLine::query()
            ->whereIn('sale_refund_id', SaleRefund::query()
                ->where('sale_id', $sale->id)->where('status', self::STATUS_APPROVED)->select('id'))
            ->sum('qty');
        $soldQty = (float) $sale->lines()->sum('qty');

        $sale->update([
            'status' => $approvedQty + 0.0005 >= $soldQty ? 'refunded' : 'partially_refunded',
        ]);
    }
}
