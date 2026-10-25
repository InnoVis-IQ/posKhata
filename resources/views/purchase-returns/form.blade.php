@extends('layouts.app')

@section('title', 'Purchase return')
@section('info', 'Send goods back to a supplier and reduce what you owe.')

@section('content')
    <form method="POST" action="{{ route('purchase-returns.store') }}" class="mx-auto max-w-2xl space-y-4 rounded-xl border border-line bg-panel p-6" x-data="{ lines: [{ product_id: '', qty: '', unit_cost: '' }] }">
        @csrf
        <p class="text-sm text-muted">Creates a vendor credit note that allocates like a payment (surplus stays unallocated).</p>
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
                <label class="mb-1 block text-sm font-medium" for="purchase_invoice_id">Linked invoice (optional)</label>
                <input id="purchase_invoice_id" name="purchase_invoice_id" type="number" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="return_date">Return date</label>
                <input id="return_date" name="return_date" type="date" value="{{ now()->toDateString() }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
        </div>
        <div class="space-y-3">
            <p class="text-sm font-medium">Lines</p>
            <template x-for="(line, index) in lines" :key="index">
                <div class="grid gap-2 md:grid-cols-3">
                    <input type="number" :name="`lines[${index}][product_id]`" x-model="line.product_id" placeholder="Product ID" class="rounded-lg border border-line px-3 py-2 text-sm" required>
                    <input type="number" step="0.001" :name="`lines[${index}][qty]`" x-model="line.qty" placeholder="Qty" class="rounded-lg border border-line px-3 py-2 text-sm" required>
                    <input type="number" step="0.01" min="0" :name="`lines[${index}][unit_cost]`" x-model="line.unit_cost" placeholder="Unit cost (PKR)" class="rounded-lg border border-line px-3 py-2 text-sm" required>
                </div>
            </template>
            <button type="button" class="text-sm text-accent" @click="lines.push({ product_id: '', qty: '', unit_cost: '' })">+ Add line</button>
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Post return</button>
    </form>
@endsection
