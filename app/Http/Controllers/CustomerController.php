<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('customers.index', ['customers' => collect()]);
    }

    public function create(): View
    {
        return view('customers.form', ['customer' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $customer): View
    {
        return view('customers.form', [
            'customer' => (object) [
                'id' => $customer,
                'name' => '',
                'phone' => null,
                'email' => null,
                'address' => null,
                'khaata_warning_amount' => null,
                'is_active' => true,
            ],
        ]);
    }

    public function update(Request $request, int $customer): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function ledger(int $customer): View
    {
        return view('customers.ledger', [
            'customer' => (object) [
                'id' => $customer,
                'name' => 'Customer',
                'phone' => null,
                'balance' => 0,
                'warning_amount' => 0,
            ],
            'entries' => collect(),
            'overWarning' => false,
        ]);
    }

    public function paymentForm(int $customer): View
    {
        return view('customers.payment', [
            'customer' => (object) [
                'id' => $customer,
                'name' => 'Customer',
                'balance' => 0,
            ],
        ]);
    }

    public function storePayment(Request $request, int $customer): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
