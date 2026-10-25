@extends('layouts.app')

@section('title', 'Products')
@section('info', 'Everything you sell — names, prices, and when to reorder.')

@section('content')
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('products.low-stock') }}" class="rounded-lg border border-line px-3 py-2 text-sm hover:bg-surface">Low stock</a>
        <a href="{{ route('products.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add product</a>
    </div>
    @if ($products->isEmpty())
        @include('components.empty', ['message' => 'No products yet.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">SKU</th>
                        <th class="px-4 py-3 font-medium">Unit</th>
                        <th class="px-4 py-3 font-medium">Final unit price</th>
                        <th class="px-4 py-3 font-medium">Batch</th>
                        <th class="px-4 py-3 font-medium">Reorder</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $product->name }}</td>
                            <td class="px-4 py-3">{{ $product->sku }}</td>
                            <td class="px-4 py-3">{{ $product->unit_code ?? $product->unit ?? '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ \App\Support\Money::format($product->final_unit_price) }}</td>
                            <td class="px-4 py-3">
                                @if (($product->batch_size ?? 0) > 0)
                                    {{ $product->batch_size }} / −{{ \App\Support\Money::format($product->batch_discount) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $product->reorder_level }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('products.edit', $product->id) }}" class="text-accent hover:underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
