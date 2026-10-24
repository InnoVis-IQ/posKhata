<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseReceiving;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\StockLedgerEntry;
use App\Models\StockTransfer;
use App\Support\PosContext;

/**
 * Append-only stock ledger writer. Stock on hand is always SUM(qty_delta);
 * this service never touches product quantities.
 */
class StockLedgerService
{
    public const OPENING = 'opening';

    public const SALE = 'sale';

    public const SALE_RETURN = 'sale_return';

    public const PURCHASE = 'purchase';

    public const PURCHASE_RETURN = 'purchase_return';

    public const ADJUSTMENT = 'adjustment';

    public const ADJUSTMENT_DAMAGE = 'adjustment_damage';

    public const ADJUSTMENT_EXPIRY = 'adjustment_expiry';

    public const ADJUSTMENT_LOSS = 'adjustment_loss';

    public const ADJUSTMENT_TYPES = [
        self::ADJUSTMENT,
        self::ADJUSTMENT_DAMAGE,
        self::ADJUSTMENT_EXPIRY,
        self::ADJUSTMENT_LOSS,
    ];

    public const TRANSFER_OUT = 'transfer_out';

    public const TRANSFER_IN = 'transfer_in';

    /**
     * @param  array{product_id:int, qty_delta:int|float|string, movement_type:string, shop_id?:int, branch_id?:int, reference_type?:?string, reference_id?:?int, reverses_id?:?int, note?:?string, created_by?:int}  $attrs
     */
    public function append(array $attrs): StockLedgerEntry
    {
        foreach (['product_id', 'qty_delta', 'movement_type'] as $key) {
            if (! array_key_exists($key, $attrs) || $attrs[$key] === null || $attrs[$key] === '') {
                throw new \InvalidArgumentException("Stock ledger entry requires '{$key}'.");
            }
        }

        if (! is_numeric($attrs['qty_delta'])) {
            throw new \InvalidArgumentException('qty_delta must be numeric.');
        }

        $qty = round((float) $attrs['qty_delta'], 3);
        if ($qty == 0.0) {
            throw new \InvalidArgumentException('qty_delta must not be zero.');
        }

        $shopId = (int) ($attrs['shop_id'] ?? PosContext::shopId());
        $branchId = (int) ($attrs['branch_id'] ?? PosContext::branchId());
        $createdBy = (int) ($attrs['created_by'] ?? PosContext::user()->id);

        if (! Product::query()->whereKey($attrs['product_id'])->where('shop_id', $shopId)->exists()) {
            throw new \InvalidArgumentException("Product #{$attrs['product_id']} does not belong to shop #{$shopId}.");
        }

        if (! Branch::query()->whereKey($branchId)->where('shop_id', $shopId)->exists()) {
            throw new \InvalidArgumentException("Branch #{$branchId} does not belong to shop #{$shopId}.");
        }

        $entry = new StockLedgerEntry;
        $entry->forceFill([
            'shop_id' => $shopId,
            'branch_id' => $branchId,
            'product_id' => (int) $attrs['product_id'],
            'qty_delta' => number_format($qty, 3, '.', ''),
            'movement_type' => (string) $attrs['movement_type'],
            'reference_type' => $attrs['reference_type'] ?? null,
            'reference_id' => $attrs['reference_id'] ?? null,
            'reverses_id' => $attrs['reverses_id'] ?? null,
            'note' => $attrs['note'] ?? null,
            'created_by' => $createdBy,
            'created_at' => now(),
        ]);
        $entry->save();

        return $entry;
    }

    public function opening(Product $product, float $qty, ?string $note = null, ?int $branchId = null): StockLedgerEntry
    {
        return $this->append([
            'shop_id' => $product->shop_id,
            'product_id' => $product->id,
            'qty_delta' => $this->positive($qty),
            'movement_type' => self::OPENING,
            'note' => $note ?? 'Opening stock',
        ] + $this->branchless($branchId));
    }

    public function saleOut(Product $product, float $qty, Sale $sale, ?string $note = null): StockLedgerEntry
    {
        return $this->append([
            'shop_id' => $sale->shop_id,
            'branch_id' => $sale->branch_id,
            'product_id' => $product->id,
            'qty_delta' => -$this->positive($qty),
            'movement_type' => self::SALE,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'note' => $note,
        ]);
    }

    public function saleReturn(Product $product, float $qty, SaleRefund $refund, ?string $note = null): StockLedgerEntry
    {
        return $this->append([
            'shop_id' => $refund->shop_id,
            'branch_id' => $refund->branch_id,
            'product_id' => $product->id,
            'qty_delta' => $this->positive($qty),
            'movement_type' => self::SALE_RETURN,
            'reference_type' => 'sale_refund',
            'reference_id' => $refund->id,
            'note' => $note,
        ]);
    }

    public function purchaseIn(Product $product, float $qty, PurchaseReceiving $receiving, ?string $note = null): StockLedgerEntry
    {
        return $this->append([
            'shop_id' => $receiving->shop_id,
            'branch_id' => $receiving->branch_id,
            'product_id' => $product->id,
            'qty_delta' => $this->positive($qty),
            'movement_type' => self::PURCHASE,
            'reference_type' => 'purchase_receiving',
            'reference_id' => $receiving->id,
            'note' => $note,
        ]);
    }

    public function purchaseReturn(Product $product, float $qty, PurchaseReturn $return, ?string $note = null): StockLedgerEntry
    {
        return $this->append([
            'shop_id' => $return->shop_id,
            'branch_id' => $return->branch_id,
            'product_id' => $product->id,
            'qty_delta' => -$this->positive($qty),
            'movement_type' => self::PURCHASE_RETURN,
            'reference_type' => 'purchase_return',
            'reference_id' => $return->id,
            'note' => $note,
        ]);
    }

    /**
     * Manual correction. $qtyDelta may be positive or negative; a note (reason) is mandatory.
     * $movementType may be the generic 'adjustment' or one of the reason-specific types
     * (adjustment_damage | adjustment_expiry | adjustment_loss).
     */
    public function adjust(
        Product $product,
        float $qtyDelta,
        string $note,
        ?int $branchId = null,
        string $movementType = self::ADJUSTMENT,
    ): StockLedgerEntry {
        if (trim($note) === '') {
            throw new \InvalidArgumentException('A note/reason is required for stock adjustments.');
        }

        if (! in_array($movementType, self::ADJUSTMENT_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Invalid adjustment type '{$movementType}'. Allowed: ".implode(', ', self::ADJUSTMENT_TYPES).'.'
            );
        }

        return $this->append([
            'shop_id' => $product->shop_id,
            'product_id' => $product->id,
            'qty_delta' => $qtyDelta,
            'movement_type' => $movementType,
            'note' => $note,
        ] + $this->branchless($branchId));
    }

    /** Stock on hand for a product at a branch (defaults to the current branch): SUM(qty_delta). */
    public function onHand(Product $product, ?int $branchId = null): float
    {
        return round((float) StockLedgerEntry::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $branchId ?? PosContext::branchId())
            ->sum('qty_delta'), 3);
    }

    /**
     * Writes the transfer_out (from branch, negative) and transfer_in (to branch, positive) pair.
     *
     * @return array{0: StockLedgerEntry, 1: StockLedgerEntry} [out, in]
     */
    public function transferPair(StockTransfer $transfer, Product $product, float $qty, ?string $note = null): array
    {
        if ((int) $transfer->from_branch_id === (int) $transfer->to_branch_id) {
            throw new \InvalidArgumentException('Transfer source and destination branches must differ.');
        }

        $qty = $this->positive($qty);
        $base = [
            'shop_id' => $transfer->shop_id,
            'product_id' => $product->id,
            'reference_type' => 'stock_transfer',
            'reference_id' => $transfer->id,
            'note' => $note ?? $transfer->note,
        ];

        $out = $this->append($base + [
            'branch_id' => $transfer->from_branch_id,
            'qty_delta' => -$qty,
            'movement_type' => self::TRANSFER_OUT,
        ]);

        $in = $this->append($base + [
            'branch_id' => $transfer->to_branch_id,
            'qty_delta' => $qty,
            'movement_type' => self::TRANSFER_IN,
        ]);

        return [$out, $in];
    }

    private function positive(float $qty): float
    {
        $qty = round($qty, 3);
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        return $qty;
    }

    /** Only pass branch_id through when explicitly given (so append() falls back to PosContext). */
    private function branchless(?int $branchId): array
    {
        return $branchId !== null ? ['branch_id' => $branchId] : [];
    }
}
