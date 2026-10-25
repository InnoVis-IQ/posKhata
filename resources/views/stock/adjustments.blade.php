@extends('layouts.app')

@section('title', 'Stock adjustments')
@section('info', 'Fix stock when counts don’t match — damage, loss, or found goods.')

@section('content')
    <form method="POST" action="{{ route('stock.adjustments.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
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
            <label class="mb-1 block text-sm font-medium" for="movement_type">Reason</label>
            <select id="movement_type" name="movement_type" class="w-full rounded-lg border border-line px-3 py-2 text-sm" required>
                <option value="adjustment_damage">Damage</option>
                <option value="adjustment_expiry">Expiry</option>
                <option value="adjustment_loss">Loss</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="qty">Quantity to remove</label>
            <input id="qty" name="qty" type="number" step="0.001" min="0.001" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="note">Note (required)</label>
            <textarea id="note" name="note" rows="2" required class="w-full rounded-lg border border-line px-3 py-2 text-sm"></textarea>
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Post adjustment</button>
    </form>
@endsection
