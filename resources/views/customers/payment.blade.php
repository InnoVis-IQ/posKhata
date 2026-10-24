@extends('layouts.app')

@section('title', 'Khaata payment · '.$customer->name)

@section('content')
    <form method="POST" action="{{ route('customers.payment.store', $customer->id) }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        <p class="text-sm text-muted">Current balance: {{ $customer->balance }} (minor units). Writes <code>khaata_payments</code> + ledger <code>payment</code> entry in Tier 3.</p>
        <div>
            <label class="mb-1 block text-sm font-medium" for="amount">Amount</label>
            <input id="amount" name="amount" type="number" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="method">Method</label>
            <select id="method" name="method" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank">Bank</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="receipt_number">Receipt number</label>
            <input id="receipt_number" name="receipt_number" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="received_at">Received at</label>
            <input id="received_at" name="received_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="note">Note</label>
            <input id="note" name="note" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Record payment</button>
    </form>
@endsection
