<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\RegisterSession;
use App\Services\RegisterService;
use App\Support\Money;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Open to every authenticated role; reopening a closed day is admin-only. */
class RegisterController extends Controller
{
    use GatesRoles;

    public function __construct(private readonly RegisterService $register) {}

    public function show(): View
    {
        $session = $this->register->getOpenSession();

        return view('register.show', [
            'session' => $session,
            'movements' => $session ? $session->cashMovements()->latest('id')->get() : collect(),
            'expectedCash' => $session ? $this->register->expectedCash($session) : null,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate(['opening_float' => ['required', 'numeric', 'min:0']]);

        try {
            $this->register->open(Money::round($data['opening_float']));
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('register.show')->with('status', 'Register opened.');
    }

    public function movement(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(RegisterService::DIRECTIONS)],
            'reason' => ['required', Rule::in(RegisterService::REASONS)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->register->addMovement($data['direction'], $data['reason'], Money::round($data['amount']), $data['note'] ?? null);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('register.show')->with('status', 'Cash movement posted.');
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate(['counted_cash' => ['required', 'numeric', 'min:0']]);

        try {
            $session = $this->register->close(Money::round($data['counted_cash']));
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('register.show')->with(
            'status',
            "Register closed. Expected {$session->expected_cash}, counted {$session->counted_cash}, variance {$session->variance} PKR."
        );
    }

    public function reopen(Request $request): RedirectResponse
    {
        abort_unless(PosContext::isAdmin(), 403, 'Only an admin can reopen a register session.');

        try {
            $last = RegisterSession::query()
                ->where('shop_id', PosContext::shopId())
                ->where('branch_id', PosContext::branchId())
                ->where('status', RegisterService::STATUS_CLOSED)
                ->latest('closed_at')
                ->latest('id')
                ->first();

            if ($last === null) {
                throw new \RuntimeException('There is no closed register session to reopen at this branch.');
            }

            $this->register->reopen($last);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('register.show')->with('status', 'Last closed session reopened.');
    }
}
