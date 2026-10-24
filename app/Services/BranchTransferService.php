<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Support\PosContext;
use Illuminate\Support\Facades\DB;

class BranchTransferService
{
    public function __construct(private readonly StockLedgerService $stock) {}

    /**
     * @param  array{
     *   from_branch_id?: int,
     *   to_branch_id: int,
     *   transferred_at?: mixed,
     *   note?: ?string,
     *   lines: array<int, array{product_id:int, qty:int|float|string}>
     * }  $data  from_branch_id defaults to the current branch.
     */
    public function create(array $data): StockTransfer
    {
        $lines = $data['lines'] ?? [];
        if (! is_array($lines) || $lines === []) {
            throw new \InvalidArgumentException('A transfer needs at least one line.');
        }

        $user = PosContext::user();
        $shopId = PosContext::shopId();
        $from = (int) ($data['from_branch_id'] ?? PosContext::branchId());
        $to = (int) ($data['to_branch_id'] ?? 0);

        if ($to <= 0) {
            throw new \InvalidArgumentException('to_branch_id is required.');
        }
        if ($from === $to) {
            throw new \InvalidArgumentException('Source and destination branches must be different.');
        }

        return DB::transaction(function () use ($data, $lines, $user, $shopId, $from, $to) {
            $count = Branch::query()->where('shop_id', $shopId)->where('is_active', true)->whereIn('id', [$from, $to])->count();
            if ($count !== 2) {
                throw new \InvalidArgumentException('Both branches must be active branches of this shop.');
            }

            $products = Product::query()
                ->where('shop_id', $shopId)
                ->whereIn('id', collect($lines)->pluck('product_id')->filter()->unique()->all())
                ->get()->keyBy('id');

            $normalised = [];
            foreach ($lines as $i => $line) {
                $product = $products->get((int) ($line['product_id'] ?? 0));
                if ($product === null) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': product not found in this shop.');
                }
                $qty = round((float) ($line['qty'] ?? 0), 3);
                if ($qty <= 0) {
                    throw new \InvalidArgumentException('Line '.($i + 1).': qty must be greater than zero.');
                }
                $normalised[] = [$product, $qty];
            }

            $transfer = StockTransfer::create([
                'shop_id' => $shopId,
                'from_branch_id' => $from,
                'to_branch_id' => $to,
                'transferred_at' => $data['transferred_at'] ?? now(),
                'note' => $data['note'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($normalised as [$product, $qty]) {
                $transfer->lines()->create([
                    'shop_id' => $shopId,
                    'product_id' => $product->id,
                    'qty' => number_format($qty, 3, '.', ''),
                ]);

                $this->stock->transferPair($transfer, $product, $qty);
            }

            return $transfer->load('lines');
        });
    }
}
