@extends('layouts.app')

@section('title', 'Receive '.$invoice->invoice_number)

@section('content')
    <form method="POST" action="{{ route('purchases.receive.store', $invoice->id) }}" class="mx-auto max-w-2xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        <p class="text-sm text-muted">Vendor: {{ $invoice->vendor_name }}. Receiving updates stock ledger and last unit cost.</p>
        @if ($invoice->lines->isEmpty())
            @include('components.empty', ['message' => 'No invoice lines loaded (Tier 3).'])
        @else
            <div class="space-y-3">
                @foreach ($invoice->lines as $i => $line)
                    <div class="grid gap-2 md:grid-cols-3">
                        <input type="hidden" name="lines[{{ $i }}][product_id]" value="{{ $line->product_id }}">
                        <p class="text-sm md:col-span-1">{{ $line->product_name }}</p>
                        <input type="number" step="0.001" name="lines[{{ $i }}][qty]" value="{{ $line->qty }}" class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Qty">
                        <input type="number" name="lines[{{ $i }}][unit_cost]" value="{{ $line->unit_cost }}" class="rounded-lg border border-line px-3 py-2 text-sm" placeholder="Unit cost">
                    </div>
                @endforeach
            </div>
        @endif
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Receive stock</button>
    </form>
@endsection
