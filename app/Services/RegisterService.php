<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\KhaataPayment;
use App\Models\RegisterCashMovement;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleRefundPayment;
use App\Models\VendorPayment;
use App\Support\Money;
use App\Support\PosContext;
use Illuminate\Support\Facades\DB;

/** All money amounts are major currency units (PKR). */
class RegisterService
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const REASONS = ['expense', 'float_adjust', 'other'];

    public const DIRECTIONS = ['in', 'out'];

    public function getOpenSession(?int $branchId = null): ?RegisterSession
    {
        return RegisterSession::query()
            ->where('shop_id', PosContext::shopId())
            ->where('branch_id', $branchId ?? PosContext::branchId())
            ->where('status', self::STATUS_OPEN)
            ->latest('id')
            ->first();
    }

    public function requireOpenSession(?int $branchId = null): RegisterSession
    {
        $session = $this->getOpenSession($branchId);
        if ($session === null) {
            throw new \RuntimeException('No open register session for this branch. Open the register first.');
        }

        return $session;
    }

    public function open(int|float $openingFloat): RegisterSession
    {
        $float = Money::round($openingFloat);
        if ($float < 0) {
            throw new \InvalidArgumentException('Opening float cannot be negative.');
        }

        $user = PosContext::user();
        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();

        return DB::transaction(function () use ($float, $user, $shopId, $branchId) {
            // Serialise concurrent opens per branch.
            Branch::query()->whereKey($branchId)->where('shop_id', $shopId)->lockForUpdate()->firstOrFail();

            $exists = RegisterSession::query()
                ->where('branch_id', $branchId)
                ->where('status', self::STATUS_OPEN)
                ->exists();
            if ($exists) {
                throw new \RuntimeException('A register session is already open for this branch.');
            }

            return RegisterSession::create([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'opened_by' => $user->id,
                'opened_at' => now(),
                'opening_float' => $float,
                'status' => self::STATUS_OPEN,
            ]);
        });
    }

    /** Closes $session (default: the branch's open session) and stores expected/counted/variance. */
    public function close(int|float $countedCash, ?RegisterSession $session = null): RegisterSession
    {
        $counted = Money::round($countedCash);
        if ($counted < 0) {
            throw new \InvalidArgumentException('Counted cash cannot be negative.');
        }

        $user = PosContext::user();
        $session ??= $this->requireOpenSession();

        return DB::transaction(function () use ($session, $counted, $user) {
            $locked = $this->lockedSession($session);

            if ($locked->status !== self::STATUS_OPEN) {
                throw new \RuntimeException('Register session is not open.');
            }

            $expected = $this->expectedCash($locked);

            $locked->update([
                'status' => self::STATUS_CLOSED,
                'closed_by' => $user->id,
                'closed_at' => now(),
                'expected_cash' => $expected,
                'counted_cash' => $counted,
                'variance' => Money::round($counted - $expected),
            ]);

            return $locked->refresh();
        });
    }

    public function reopen(RegisterSession $session): RegisterSession
    {
        $user = PosContext::user();

        return DB::transaction(function () use ($session, $user) {
            $locked = $this->lockedSession($session);

            if ($locked->status !== self::STATUS_CLOSED) {
                throw new \RuntimeException('Only a closed register session can be reopened.');
            }

            Branch::query()->whereKey($locked->branch_id)->lockForUpdate()->firstOrFail();

            $other = RegisterSession::query()
                ->where('branch_id', $locked->branch_id)
                ->where('status', self::STATUS_OPEN)
                ->where('id', '!=', $locked->id)
                ->exists();
            if ($other) {
                throw new \RuntimeException('Another register session is already open for this branch.');
            }

            $locked->update([
                'status' => self::STATUS_OPEN,
                'reopened_by' => $user->id,
                'reopened_at' => now(),
                // Close figures are recomputed on the next close.
                'closed_by' => null,
                'closed_at' => null,
                'expected_cash' => null,
                'counted_cash' => null,
                'variance' => null,
            ]);

            return $locked->refresh();
        });
    }

    public function addMovement(string $direction, string $reason, int|float $amount, ?string $note = null): RegisterCashMovement
    {
        if (! in_array($direction, self::DIRECTIONS, true)) {
            throw new \InvalidArgumentException("Direction must be one of: ".implode('|', self::DIRECTIONS).'.');
        }
        if (! in_array($reason, self::REASONS, true)) {
            throw new \InvalidArgumentException("Reason must be one of: ".implode('|', self::REASONS).'.');
        }

        $amount = Money::round($amount);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Movement amount must be greater than zero.');
        }

        $user = PosContext::user();

        return DB::transaction(function () use ($direction, $reason, $amount, $note, $user) {
            $session = $this->requireOpenSession();
            $session = $this->lockedSession($session);
            if ($session->status !== self::STATUS_OPEN) {
                throw new \RuntimeException('Register session is not open.');
            }

            return RegisterCashMovement::create([
                'shop_id' => $session->shop_id,
                'branch_id' => $session->branch_id,
                'register_session_id' => $session->id,
                'direction' => $direction,
                'reason' => $reason,
                'amount' => $amount,
                'note' => $note,
                'created_by' => $user->id,
            ]);
        });
    }

    /**
     * expected = opening_float
     *          + cash sale payments (sales in session)
     *          + cash khaata payments (session)
     *          - cash vendor payments (session)
     *          - cash sale refund payments (session)
     *          + cash movements in - out
     */
    public function expectedCash(RegisterSession $session): float
    {
        $id = $session->id;

        $salesCash = Money::round(SalePayment::query()
            ->where('method', 'cash')
            ->whereIn('sale_id', Sale::query()->where('register_session_id', $id)->select('id'))
            ->sum('amount'));

        $khaataCash = Money::round(KhaataPayment::query()
            ->where('register_session_id', $id)->where('method', 'cash')->sum('amount'));

        $vendorCash = Money::round(VendorPayment::query()
            ->where('register_session_id', $id)->where('method', 'cash')->sum('amount'));

        $refundCash = Money::round(SaleRefundPayment::query()
            ->where('register_session_id', $id)->where('method', 'cash')->sum('amount'));

        $in = Money::round(RegisterCashMovement::query()
            ->where('register_session_id', $id)->where('direction', 'in')->sum('amount'));
        $out = Money::round(RegisterCashMovement::query()
            ->where('register_session_id', $id)->where('direction', 'out')->sum('amount'));

        return Money::round(
            (float) $session->opening_float
            + $salesCash
            + $khaataCash
            - $vendorCash
            - $refundCash
            + $in
            - $out
        );
    }

    private function lockedSession(RegisterSession $session): RegisterSession
    {
        $locked = RegisterSession::query()
            ->whereKey($session->id)
            ->where('shop_id', PosContext::shopId())
            ->lockForUpdate()
            ->first();

        if ($locked === null) {
            throw new \RuntimeException("Register session #{$session->id} not found for this shop.");
        }

        return $locked;
    }
}
