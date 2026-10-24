<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\BranchTransferService;
use App\Services\StockLedgerService;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public function __construct(
        private readonly StockLedgerService $ledger,
        private readonly BranchTransferService $transfers,
    ) {}

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function opening(): View
    {
        return view('stock.opening', ['products' => $this->products()]);
    }

    public function storeOpening(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $product = $this->findProduct((int) $data['product_id']);
            $this->ledger->opening($product, (float) $data['qty'], $data['note'] ?? null);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('stock.opening')->with('status', "Opening stock posted for {$product->name}.");
    }

    public function adjustments(): View
    {
        return view('stock.adjustments', ['products' => $this->products()]);
    }

    /** The form removes stock: qty is the (positive) quantity to take out of the current branch. */
    public function storeAdjustment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'movement_type' => ['required', Rule::in([
                StockLedgerService::ADJUSTMENT_DAMAGE,
                StockLedgerService::ADJUSTMENT_EXPIRY,
                StockLedgerService::ADJUSTMENT_LOSS,
            ])],
            'qty' => ['required', 'numeric', 'gt:0'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $product = $this->findProduct((int) $data['product_id']);
            $qty = round((float) $data['qty'], 3);

            $onHand = $this->ledger->onHand($product);
            if ($qty > $onHand + 0.0005) {
                throw new \RuntimeException("Cannot remove {$qty} of {$product->name}: only {$onHand} on hand at this branch.");
            }

            $this->ledger->adjust($product, -$qty, $data['note'], null, $data['movement_type']);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('stock.adjustments')->with('status', "Adjustment posted for {$product->name}.");
    }

    public function transfers(): View
    {
        $transfers = StockTransfer::query()
            ->where('shop_id', PosContext::shopId())
            ->with(['fromBranch', 'toBranch'])
            ->latest('id')
            ->limit(200)
            ->get()
            ->each(function (StockTransfer $t) {
                $t->setAttribute('from_branch', $t->fromBranch?->name);
                $t->setAttribute('to_branch', $t->toBranch?->name);
            });

        return view('stock.transfers.index', ['transfers' => $transfers]);
    }

    public function createTransfer(): View
    {
        return view('stock.transfers.form', ['products' => $this->products()]);
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_branch_id' => ['required', 'integer'],
            'to_branch_id' => ['required', 'integer', 'different:from_branch_id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $from = (int) $data['from_branch_id'];

            // Do not let a transfer drive the source branch negative.
            $needed = [];
            foreach ($data['lines'] as $line) {
                $pid = (int) $line['product_id'];
                $needed[$pid] = ($needed[$pid] ?? 0) + round((float) $line['qty'], 3);
            }
            foreach ($needed as $pid => $qty) {
                $product = $this->findProduct($pid);
                $onHand = $this->ledger->onHand($product, $from);
                if ($qty > $onHand + 0.0005) {
                    throw new \RuntimeException("Cannot transfer {$qty} of {$product->name}: only {$onHand} on hand at the source branch.");
                }
            }

            $this->transfers->create([
                'from_branch_id' => $from,
                'to_branch_id' => (int) $data['to_branch_id'],
                'note' => $data['note'] ?? null,
                'lines' => collect($data['lines'])->map(fn ($l) => [
                    'product_id' => (int) $l['product_id'],
                    'qty' => $l['qty'],
                ])->values()->all(),
            ]);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('stock.transfers.index')->with('status', 'Transfer posted.');
    }

    private function products()
    {
        return Product::query()
            ->where('shop_id', PosContext::shopId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function findProduct(int $id): Product
    {
        $product = Product::query()->where('shop_id', PosContext::shopId())->find($id);
        if ($product === null) {
            throw new \InvalidArgumentException("Product #{$id} not found in this shop.");
        }

        return $product;
    }
}
