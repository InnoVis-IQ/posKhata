@extends('layouts.app')

@section('title', 'Stock log')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('logs.stock') }}" class="rounded-lg bg-accent px-3 py-1.5 font-medium text-white">Stock in/out</a>
        <a href="{{ route('logs.payments') }}" class="rounded-lg border border-line px-3 py-1.5 hover:bg-surface">Payments in/out</a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-sm font-medium" for="date_from">From</label>
            <input id="date_from" name="date_from" type="date" value="{{ $dateFrom }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="date_to">To</label>
            <input id="date_to" name="date_to" type="date" value="{{ $dateTo }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="direction">Direction</label>
            <select id="direction" name="direction" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="in" @selected(($filters['direction'] ?? '') === 'in')>In</option>
                <option value="out" @selected(($filters['direction'] ?? '') === 'out')>Out</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="movement_type">Type</label>
            <select id="movement_type" name="movement_type" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">All</option>
                @foreach ($movementTypes as $type)
                    <option value="{{ $type }}" @selected(($filters['movement_type'] ?? '') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="product_id">Product</label>
            <select id="product_id" name="product_id" class="min-w-48 rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">All</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>
                        {{ $product->name }}@if ($product->sku) ({{ $product->sku }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Show</button>
    </form>

    @if ($entries->isEmpty())
        @include('components.empty', ['message' => 'No stock movements for this filter.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">Dir</th>
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium text-right">Qty</th>
                        <th class="px-4 py-3 font-medium">Ref</th>
                        <th class="px-4 py-3 font-medium">By</th>
                        <th class="px-4 py-3 font-medium">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        @php
                            $in = (float) $entry->qty_delta > 0;
                            $qty = abs((float) $entry->qty_delta);
                        @endphp
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $entry->created_at }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $in ? 'text-emerald-700' : 'text-rose-700' }}">{{ $in ? 'in' : 'out' }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $entry->product?->name ?? ('#'.$entry->product_id) }}</td>
                            <td class="px-4 py-3">{{ $entry->movement_type }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $in ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $in ? '+' : '−' }}{{ number_format($qty, 3) }}
                            </td>
                            <td class="px-4 py-3 text-muted">
                                @if ($entry->reference_type)
                                    {{ $entry->reference_type }} #{{ $entry->reference_id }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $entry->creator?->name }}</td>
                            <td class="px-4 py-3">{{ $entry->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $entries->links() }}</div>
    @endif
@endsection
