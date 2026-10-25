@extends('layouts.app')

@section('title', 'Low stock')
@section('info', 'Items that are running low and may need a reorder soon.')

@section('content')
    <p class="mb-4 text-sm text-muted">Products where derived branch qty ≤ reorder level.</p>
    @if ($products->isEmpty())
        @include('components.empty', ['message' => 'No low-stock items to show.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Qty</th>
                        <th class="px-4 py-3 font-medium">Reorder</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $product->name }}</td>
                            <td class="px-4 py-3">{{ $product->qty }}</td>
                            <td class="px-4 py-3">{{ $product->reorder_level }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
