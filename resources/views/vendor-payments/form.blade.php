@extends('layouts.app')

@section('title', 'Vendor payment')
@section('info', 'Record money you paid a supplier.')

@section('content')
    <form method="POST" action="{{ route('vendor-payments.store') }}" class="mx-auto max-w-2xl space-y-4 rounded-xl border border-line bg-panel p-6" x-data="{ manual: false, allocations: [{ purchase_invoice_id: '', amount: '' }] }">
        @csrf
        <p class="text-sm text-muted">Default allocation is FIFO to oldest unpaid invoices; enable manual override below.</p>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium" for="vendor_id">Vendor</label>
                <select id="vendor_id" name="vendor_id" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="">Select…</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="amount">Amount (PKR)</label>
                <input id="amount" name="amount" type="number" step="0.01" min="0.01" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="method">Method</label>
                <select id="method" name="method" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="cash">Cash</option>
                    <option value="bank">Bank</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="paid_at">Paid at</label>
                <input id="paid_at" name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="manual_allocation" value="1" x-model="manual">
            Manual allocation override
        </label>
        <div class="space-y-2" x-show="manual" x-cloak>
            <template x-for="(row, index) in allocations" :key="index">
                <div class="grid gap-2 md:grid-cols-2">
                    <input type="number" :name="`allocations[${index}][purchase_invoice_id]`" x-model="row.purchase_invoice_id" placeholder="Invoice ID" class="rounded-lg border border-line px-3 py-2 text-sm">
                    <input type="number" step="0.01" min="0" :name="`allocations[${index}][amount]`" x-model="row.amount" placeholder="Amount (PKR)" class="rounded-lg border border-line px-3 py-2 text-sm">
                </div>
            </template>
            <button type="button" class="text-sm text-accent" @click="allocations.push({ purchase_invoice_id: '', amount: '' })">+ Allocation row</button>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="note">Note</label>
            <input id="note" name="note" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Record payment</button>
    </form>
@endsection
