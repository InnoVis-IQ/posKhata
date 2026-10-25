@extends('layouts.app')

@section('title', 'Day sales')
@section('info', 'See how much you sold on a chosen day.')

@section('content')
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-sm font-medium" for="date">Date</label>
            <input id="date" name="date" type="date" value="{{ $date }}" class="rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Show</button>
    </form>
    @if ($sales->isEmpty())
        @include('components.empty', ['message' => 'No sales for this date.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Sale #</th>
                        <th class="px-4 py-3 font-medium">Time</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sales as $sale)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $sale->sale_number }}</td>
                            <td class="px-4 py-3">{{ $sale->sold_at }}</td>
                            <td class="px-4 py-3">{{ $sale->total }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('sales.receipt', $sale->id) }}" class="text-accent hover:underline">Receipt</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
