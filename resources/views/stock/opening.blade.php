@extends('layouts.app')

@section('title', 'Opening stock')

@section('content')
    <form method="POST" action="{{ route('stock.opening.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        <p class="text-sm text-muted">Posts <code>opening</code> ledger rows for the current branch.</p>
        <div>
            <label class="mb-1 block text-sm font-medium" for="product_id">Product</label>
            <select id="product_id" name="product_id" class="w-full rounded-lg border border-line px-3 py-2 text-sm" required>
                <option value="">Select…</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="qty">Quantity</label>
            <input id="qty" name="qty" type="number" step="0.001" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="note">Note</label>
            <input id="note" name="note" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Post opening</button>
    </form>
@endsection
