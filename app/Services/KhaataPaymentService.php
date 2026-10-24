<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\KhaataEntry;
use App\Models\KhaataPayment;
use App\Models\RegisterSession;
use App\Support\NumberSequence;
use App\Support\PosContext;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class KhaataPaymentService
{
    public function __construct(private readonly RegisterService $register) {}

    /** Current outstanding balance (positive = customer owes the shop). */
    public function balance(Customer $customer): int
    {
        return (int) KhaataEntry::query()->where('customer_id', $customer->id)->sum('amount_delta');
    }

    /**
     * Records a customer payment against their khaata. Creates a khaata_payments row and a
     * khaata_entries row (entry_type=payment, negative amount_delta). Cash requires an open register.
     *
     * @param  int|float  $amount  minor units
     */
    public function record(
        Customer $customer,
        int|float $amount,
        string $method,
        ?string $receipt_number = null,
        CarbonInterface|string|null $received_at = null,
        ?string $note = null,
        ?int $register_session_id = null,
    ): KhaataPayment {
        $amount = (int) round($amount);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $method = strtolower(trim($method));
        if ($method === '' || $method === 'khaata') {
            throw new \InvalidArgumentException("Invalid khaata payment method '{$method}'.");
        }

        $user = PosContext::user();
        $shopId = PosContext::shopId();
        $branchId = PosContext::branchId();

        if ((int) $customer->shop_id !== $shopId) {
            throw new \InvalidArgumentException("Customer #{$customer->id} does not belong to this shop.");
        }

        return DB::transaction(function () use (
            $customer, $amount, $method, $receipt_number, $received_at, $note, $register_session_id,
            $user, $shopId, $branchId
        ) {
            $session = null;
            if ($register_session_id !== null) {
                $session = RegisterSession::query()
                    ->whereKey($register_session_id)
                    ->where('shop_id', $shopId)
                    ->first();
                if ($session === null) {
                    throw new \InvalidArgumentException("Register session #{$register_session_id} not found.");
                }
                if ($method === 'cash' && $session->status !== RegisterService::STATUS_OPEN) {
                    throw new \RuntimeException('Cash payments require an open register session.');
                }
            } elseif ($method === 'cash') {
                $session = $this->register->requireOpenSession($branchId);
            } else {
                // Non-cash: attach to the open session when there is one, but do not require it.
                $session = $this->register->getOpenSession($branchId);
            }

            $receipt = $receipt_number !== null && trim($receipt_number) !== ''
                ? trim($receipt_number)
                : NumberSequence::next($shopId, 'khaata_payments', 'receipt_number', 'KP-'.now()->format('Ymd').'-');

            $payment = KhaataPayment::create([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'method' => $method,
                'received_at' => $received_at ?? now(),
                'receipt_number' => $receipt,
                'register_session_id' => $session?->id,
                'created_by' => $user->id,
                'note' => $note,
            ]);

            KhaataEntry::create([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'amount_delta' => -$amount,
                'entry_type' => 'payment',
                'reference_type' => 'khaata_payment',
                'reference_id' => $payment->id,
                'note' => $note,
                'created_by' => $user->id,
            ]);

            return $payment;
        });
    }
}
