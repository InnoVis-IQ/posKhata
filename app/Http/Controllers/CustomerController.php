<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Customer;
use App\Models\KhaataEntry;
use App\Services\KhaataPaymentService;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Open to every authenticated role (cashiers manage customers and record khaata payments). */
class CustomerController extends Controller
{
    use GatesRoles;

    public function __construct(private readonly KhaataPaymentService $khaata) {}

    public function index(): View
    {
        return view('customers.index', [
            'customers' => Customer::query()->where('shop_id', PosContext::shopId())->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Customer::create($this->validated($request) + ['shop_id' => PosContext::shopId()]);

        return redirect()->route('customers.index')->with('status', 'Customer created.');
    }

    public function edit(int $customer): View
    {
        return view('customers.form', ['customer' => $this->find($customer)]);
    }

    public function update(Request $request, int $customer): RedirectResponse
    {
        $model = $this->find($customer);
        $model->update($this->validated($request, $model));

        return redirect()->route('customers.index')->with('status', 'Customer updated.');
    }

    public function ledger(int $customer): View
    {
        $model = $this->find($customer);
        $balance = $this->khaata->balance($model);
        $threshold = $this->threshold($model);

        $model->setAttribute('balance', $balance);
        $model->setAttribute('warning_amount', $threshold);

        return view('customers.ledger', [
            'customer' => $model,
            'entries' => KhaataEntry::query()
                ->where('customer_id', $model->id)
                ->latest('id')
                ->limit(500)
                ->get(),
            'overWarning' => $threshold > 0 && $balance >= $threshold,
        ]);
    }

    public function paymentForm(int $customer): View
    {
        $model = $this->find($customer);
        $model->setAttribute('balance', $this->khaata->balance($model));

        return view('customers.payment', ['customer' => $model]);
    }

    public function storePayment(Request $request, int $customer): RedirectResponse
    {
        $model = $this->find($customer);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(['cash', 'card', 'bank', 'other'])],
            'receipt_number' => [
                'nullable', 'string', 'max:50',
                Rule::unique('khaata_payments', 'receipt_number')->where('shop_id', PosContext::shopId()),
            ],
            'received_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $payment = $this->khaata->record(
                $model,
                (int) $data['amount'],
                $data['method'],
                $data['receipt_number'] ?? null,
                $data['received_at'] ?? null,
                $data['note'] ?? null,
            );
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        $redirect = redirect()->route('customers.ledger', $model->id)
            ->with('status', "Payment {$payment->receipt_number} recorded.");

        $threshold = $this->threshold($model);
        if ($threshold > 0 && $this->khaata->balance($model) >= $threshold) {
            $redirect->with('warning', 'Customer balance is still at or above the khaata warning threshold.');
        }

        return $redirect;
    }

    private function find(int $id): Customer
    {
        return Customer::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    /** Per-customer override, else the shop default (minor units; 0 = no warning). */
    private function threshold(Customer $customer): int
    {
        return (int) ($customer->khaata_warning_amount ?? PosContext::shop()->default_khaata_warning_amount ?? 0);
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers', 'phone')->where('shop_id', PosContext::shopId())->ignore($customer?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'khaata_warning_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
