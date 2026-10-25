<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Sale;
use App\Models\SaleRefund;
use App\Models\SaleRefundLine;
use App\Services\RegisterService;
use App\Services\SaleRefundVoidService;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Any role may request a refund/void (create/store); listing, approving and rejecting are admin-only.
 */
class RefundController extends Controller implements HasMiddleware
{
    use GatesRoles;

    private const PAYOUT_TAG = '/\n?\[Payout: (cash|card|khaata)\]/';

    public function __construct(
        private readonly SaleRefundVoidService $refunds,
        private readonly RegisterService $register,
    ) {}

    public static function middleware(): array
    {
        return [self::adminOnly()->only(['index', 'approve', 'reject'])];
    }

    public function index(): View
    {
        $base = SaleRefund::query()->where('shop_id', PosContext::shopId())->with('sale');
        $label = fn (SaleRefund $r) => $r->setAttribute('sale_number', $r->sale?->sale_number);

        return view('refunds.index', [
            'pending' => (clone $base)->where('status', SaleRefundVoidService::STATUS_PENDING)
                ->oldest('id')->get()->each($label),
            'recent' => (clone $base)->where('status', '!=', SaleRefundVoidService::STATUS_PENDING)
                ->latest('id')->limit(20)->get()->each($label),
        ]);
    }

    public function create(): View
    {
        return view('refunds.form', [
            'sale' => null,
            'registerOpen' => $this->register->getOpenSession() !== null,
        ]);
    }

    /**
     * Creates a pending request. The form has no line picker, so a "refund" covers every line's
     * remaining refundable quantity (explicit lines[] are honoured when posted). The requested
     * payout method is stored as a tag on the reason and used when an admin approves.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sale_id' => ['required', 'integer'],
            'kind' => ['required', Rule::in([SaleRefundVoidService::KIND_REFUND, SaleRefundVoidService::KIND_VOID])],
            'reason' => ['required', 'string', 'max:2000'],
            'payout_method' => ['nullable', Rule::in(['cash', 'card', 'khaata'])],
            'lines' => ['nullable', 'array'],
            'lines.*.sale_line_id' => ['required_with:lines', 'integer'],
            'lines.*.qty' => ['required_with:lines', 'numeric', 'gt:0'],
        ]);

        try {
            $sale = Sale::query()->where('shop_id', PosContext::shopId())->find((int) $data['sale_id']);
            if ($sale === null) {
                throw new \InvalidArgumentException("Sale #{$data['sale_id']} not found in this shop.");
            }
            if (! PosContext::isAdmin() && (int) $sale->branch_id !== PosContext::branchId()) {
                throw new \InvalidArgumentException('That sale belongs to another branch.');
            }

            $payload = [
                'kind' => $data['kind'],
                'reason' => trim($data['reason']).(! empty($data['payout_method']) ? "\n[Payout: {$data['payout_method']}]" : ''),
                'restock' => $request->boolean('restock'),
            ];

            if ($data['kind'] === SaleRefundVoidService::KIND_REFUND) {
                $payload['lines'] = ! empty($data['lines'])
                    ? array_values($data['lines'])
                    : $this->remainingLines($sale);
            }

            $refund = $this->refunds->request($sale, $payload);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()
            ->route(PosContext::isAdmin() ? 'refunds.index' : 'refunds.create')
            ->with('status', "Request #{$refund->id} for sale {$sale->sale_number} submitted for approval.");
    }

    public function approve(Request $request, int $refund): RedirectResponse
    {
        $model = $this->find($refund);

        try {
            $payments = null;
            if (preg_match(self::PAYOUT_TAG, (string) $model->reason, $m)) {
                $payments = [['method' => $m[1], 'amount' => (float) $model->total]];
            }

            $this->refunds->approve($model, $payments);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('refunds.index')->with('status', "Request #{$model->id} approved.");
    }

    public function reject(Request $request, int $refund): RedirectResponse
    {
        $model = $this->find($refund);
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->refunds->reject($model, $data['note'] ?? null);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('refunds.index')->with('status', "Request #{$model->id} rejected.");
    }

    private function find(int $id): SaleRefund
    {
        return SaleRefund::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    /** Every sale line with its not-yet-refunded (pending or approved) quantity. */
    private function remainingLines(Sale $sale): array
    {
        $lines = [];
        foreach ($sale->lines()->get() as $line) {
            $used = (float) SaleRefundLine::query()
                ->where('sale_line_id', $line->id)
                ->whereIn('sale_refund_id', SaleRefund::query()
                    ->whereIn('status', [SaleRefundVoidService::STATUS_PENDING, SaleRefundVoidService::STATUS_APPROVED])
                    ->select('id'))
                ->sum('qty');
            $left = round((float) $line->qty - $used, 3);
            if ($left > 0) {
                $lines[] = ['sale_line_id' => $line->id, 'qty' => $left];
            }
        }

        return $lines;
    }
}
