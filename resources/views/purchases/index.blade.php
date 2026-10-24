@extends('layouts.app')

@section('title', 'Purchases')

@section('content')
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('purchase-returns.create') }}" class="rounded-lg border border-line px-3 py-2 text-sm hover:bg-surface">Purchase return</a>
        <a href="{{ route('vendor-payments.create') }}" class="rounded-lg border border-line px-3 py-2 text-sm hover:bg-surface">Vendor payment</a>
        <a href="{{ route('purchases.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">New invoice</a>
    </div>
    @if ($invoices->isEmpty())
        @include('components.empty', ['message' => 'No purchase invoices yet. Payment status is derived from allocations.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Invoice</th>
                        <th class="px-4 py-3 font-medium">Vendor</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3">{{ $invoice->vendor_name }}</td>
                            <td class="px-4 py-3">{{ $invoice->total }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('purchases.receive', $invoice->id) }}" class="text-accent hover:underline">Receive</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
