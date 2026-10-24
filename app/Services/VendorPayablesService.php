<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use App\Support\PosContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Vendor payables.
 *
 * Model: invoices increase what we owe; vendor_payments (cash/bank/... and "credit_note" rows
 * generated from purchase returns) decrease it. vendor_payment_allocations tie payment money to
 * invoices. A payment's unallocated remainder is a vendor credit that is applied to new invoices
 * via applyUnallocatedCreditsToInvoice().
 */
class VendorPayablesService
{
    public const CREDIT_NOTE_METHOD = 'credit_note';

    public function __construct(private readonly RegisterService $register) {}

    // ---------------------------------------------------------------- owed helpers

    public function invoiceAllocated(PurchaseInvoice $invoice): int
    {
        return (int) VendorPaymentAllocation::query()->where('purchase_invoice_id', $invoice->id)->sum('amount');
    }

    public function invoiceOwed(PurchaseInvoice $invoice): int
    {
        return max(0, (int) $invoice->total - $this->invoiceAllocated($invoice));
    }

    public function paymentUnallocated(VendorPayment $payment): int
    {
        $allocated = (int) VendorPaymentAllocation::query()->where('vendor_payment_id', $payment->id)->sum('amount');

        return max(0, (int) $payment->amount - $allocated);
    }

    /** Sum of unallocated remainders across all of the vendor's payments / credit notes. */
    public function vendorCredit(Vendor $vendor): int
    {
        $paid = (int) VendorPayment::query()->where('vendor_id', $vendor->id)->sum('amount');
        $allocated = (int) VendorPaymentAllocation::query()
            ->whereIn('vendor_payment_id', VendorPayment::query()->where('vendor_id', $vendor->id)->select('id'))
            ->sum('amount');

        return max(0, $paid - $allocated);
    }

    /** Net owed to vendor: invoices - all payments & credit notes (negative = vendor owes us / advance). */
    public function vendorOwed(Vendor $vendor): int
    {
        $invoiced = (int) PurchaseInvoice::query()->where('vendor_id', $vendor->id)->sum('total');
        $paid = (int) VendorPayment::query()->where('vendor_id', $vendor->id)->sum('amount');

        return $invoiced - $paid;
    }

    /** @return Collection<int, PurchaseInvoice> invoices with owed > 0, oldest first (FIFO order). */
    public function openInvoices(Vendor $vendor): Collection
    {
        return PurchaseInvoice::query()
            ->where('vendor_id', $vendor->id)
            ->withSum('paymentAllocations as allocated_sum', 'amount')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get()
            ->filter(fn (PurchaseInvoice $i) => (int) $i->total - (int) $i->allocated_sum > 0)
            ->values();
    }

    // ---------------------------------------------------------------- payments

    /**
     * Pay a vendor. $allocations: null => FIFO over open invoices; otherwise a map
     * [invoice_id => amount] or a list of ['purchase_invoice_id' => id, 'amount' => n].
     * Any part of $amount not allocated stays as a vendor credit. Cash requires an open register.
     *
     * @param  array<int|string, mixed>|null  $allocations
     */
    public function pay(
        Vendor $vendor,
        int|float $amount,
        string $method,
        ?array $allocations = null,
        ?string $note = null,
        CarbonInterface|string|null $paidAt = null,
    ): VendorPayment {
        $amount = (int) round($amount);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $method = strtolower(trim($method));
        if ($method === '' || $method === self::CREDIT_NOTE_METHOD || $method === 'khaata') {
            throw new \InvalidArgumentException("Invalid vendor payment method '{$method}'.");
        }

        $user = PosContext::user();
        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();

        if ((int) $vendor->shop_id !== $shopId) {
            throw new \InvalidArgumentException("Vendor #{$vendor->id} does not belong to this shop.");
        }

        return DB::transaction(function () use ($vendor, $amount, $method, $allocations, $note, $paidAt, $user, $shopId, $branchId) {
            $session = $method === 'cash'
                ? $this->register->requireOpenSession($branchId)
                : null;

            $payment = VendorPayment::create([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'vendor_id' => $vendor->id,
                'amount' => $amount,
                'method' => $method,
                'paid_at' => $paidAt ?? now(),
                'register_session_id' => $session?->id,
                'created_by' => $user->id,
                'note' => $note,
            ]);

            if ($allocations === null) {
                $this->allocateFifo($payment);
            } else {
                $this->allocateExplicit($payment, $vendor, $this->normaliseAllocations($allocations));
            }

            return $payment->load('allocations');
        });
    }

    /**
     * Allocate the unallocated remainder of $payment (optionally capped at $max) to the vendor's
     * oldest open invoices.
     *
     * @return Collection<int, VendorPaymentAllocation>
     */
    public function allocateFifo(VendorPayment $payment, ?int $max = null): Collection
    {
        return DB::transaction(function () use ($payment, $max) {
            $available = $this->paymentUnallocated($payment);
            if ($max !== null) {
                $available = min($available, $max);
            }

            $created = collect();
            if ($available <= 0) {
                return $created;
            }

            $vendor = Vendor::query()->findOrFail($payment->vendor_id);
            foreach ($this->openInvoices($vendor) as $invoice) {
                if ($available <= 0) {
                    break;
                }
                $apply = min($available, (int) $invoice->total - (int) $invoice->allocated_sum);
                $created->push($this->createAllocation($payment, $invoice, $apply));
                $available -= $apply;
            }

            return $created;
        });
    }

    /**
     * Call when a new purchase invoice is created: consumes the vendor's unallocated payments /
     * credit notes (oldest first) against it. Returns the total applied (minor units).
     */
    public function applyUnallocatedCreditsToInvoice(PurchaseInvoice $invoice): int
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = PurchaseInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $owed = $this->invoiceOwed($invoice);
            $applied = 0;

            if ($owed <= 0) {
                return 0;
            }

            $payments = VendorPayment::query()
                ->where('vendor_id', $invoice->vendor_id)
                ->orderBy('paid_at')
                ->orderBy('id')
                ->get();

            foreach ($payments as $payment) {
                if ($owed <= 0) {
                    break;
                }
                $free = $this->paymentUnallocated($payment);
                if ($free <= 0) {
                    continue;
                }
                $apply = min($free, $owed);
                $this->createAllocation($payment, $invoice, $apply);
                $owed -= $apply;
                $applied += $apply;
            }

            return $applied;
        });
    }

    /**
     * Turns a purchase return into a non-cash "credit_note" vendor payment, applied first to the
     * return's invoice (up to what is owed), then FIFO to other open invoices; the rest stays as
     * vendor credit. Idempotent per return.
     */
    public function creditNoteFromReturn(PurchaseReturn $return): ?VendorPayment
    {
        if ((int) $return->total <= 0) {
            return null;
        }

        return DB::transaction(function () use ($return) {
            $existing = VendorPayment::query()
                ->where('method', self::CREDIT_NOTE_METHOD)
                ->where('reference_type', 'purchase_return')
                ->where('reference_id', $return->id)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $payment = VendorPayment::create([
                'shop_id' => $return->shop_id,
                'branch_id' => $return->branch_id,
                'vendor_id' => $return->vendor_id,
                'amount' => (int) $return->total,
                'method' => self::CREDIT_NOTE_METHOD,
                'paid_at' => now(),
                'register_session_id' => null,
                'reference_type' => 'purchase_return',
                'reference_id' => $return->id,
                'created_by' => PosContext::user()->id,
                'note' => "Credit note for purchase return #{$return->id}",
            ]);

            if ($return->purchase_invoice_id !== null) {
                $invoice = PurchaseInvoice::query()
                    ->whereKey($return->purchase_invoice_id)
                    ->where('vendor_id', $return->vendor_id)
                    ->first();
                if ($invoice !== null) {
                    $apply = min($this->paymentUnallocated($payment), $this->invoiceOwed($invoice));
                    if ($apply > 0) {
                        $this->createAllocation($payment, $invoice, $apply);
                    }
                }
            }

            $this->allocateFifo($payment);

            return $payment->load('allocations');
        });
    }

    // ---------------------------------------------------------------- internals

    /** @return array<int, int> [invoice_id => amount] */
    private function normaliseAllocations(array $allocations): array
    {
        $map = [];
        foreach ($allocations as $key => $value) {
            if (is_array($value)) {
                $invoiceId = (int) ($value['purchase_invoice_id'] ?? $value['invoice_id'] ?? 0);
                $amt = (int) round((float) ($value['amount'] ?? 0));
            } else {
                $invoiceId = (int) $key;
                $amt = (int) round((float) $value);
            }
            if ($invoiceId <= 0 || $amt <= 0) {
                throw new \InvalidArgumentException('Each allocation needs a purchase invoice and a positive amount.');
            }
            $map[$invoiceId] = ($map[$invoiceId] ?? 0) + $amt;
        }

        return $map;
    }

    /** @param array<int, int> $map */
    private function allocateExplicit(VendorPayment $payment, Vendor $vendor, array $map): void
    {
        if (array_sum($map) > (int) $payment->amount) {
            throw new \InvalidArgumentException('Allocations exceed the payment amount.');
        }

        $invoices = PurchaseInvoice::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('id', array_keys($map))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($map as $invoiceId => $amt) {
            $invoice = $invoices->get($invoiceId);
            if ($invoice === null) {
                throw new \InvalidArgumentException("Invoice #{$invoiceId} does not belong to vendor #{$vendor->id}.");
            }
            $owed = $this->invoiceOwed($invoice);
            if ($amt > $owed) {
                throw new \InvalidArgumentException(
                    "Allocation {$amt} exceeds the amount owed ({$owed}) on invoice {$invoice->invoice_number}."
                );
            }
            $this->createAllocation($payment, $invoice, $amt);
        }
    }

    private function createAllocation(VendorPayment $payment, PurchaseInvoice $invoice, int $amount): VendorPaymentAllocation
    {
        return VendorPaymentAllocation::create([
            'shop_id' => $payment->shop_id,
            'branch_id' => $payment->branch_id,
            'vendor_payment_id' => $payment->id,
            'purchase_invoice_id' => $invoice->id,
            'amount' => $amount,
        ]);
    }
}
