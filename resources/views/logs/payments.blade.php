@extends('layouts.app')

@section('title', 'Payment log')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        <a href="{{ route('logs.stock') }}" class="rounded-lg border border-line px-3 py-1.5 hover:bg-surface">Stock in/out</a>
        <a href="{{ route('logs.payments') }}" class="rounded-lg bg-accent px-3 py-1.5 font-medium text-white">Payments in/out</a>
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
            <label class="mb-1 block text-sm font-medium" for="kind">Source</label>
            <select id="kind" name="kind" class="rounded-lg border border-line px-3 py-2 text-sm">
                <option value="">All</option>
                <option value="sale" @selected(($filters['kind'] ?? '') === 'sale')>Sale tender</option>
                <option value="khaata" @selected(($filters['kind'] ?? '') === 'khaata')>Khaata payment</option>
                <option value="vendor" @selected(($filters['kind'] ?? '') === 'vendor')>Vendor payment</option>
                <option value="refund" @selected(($filters['kind'] ?? '') === 'refund')>Refund payout</option>
                <option value="register" @selected(($filters['kind'] ?? '') === 'register')>Register cash</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Show</button>
    </form>

    @if ($entries->isEmpty())
        @include('components.empty', ['message' => 'No payment transactions for this filter.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">Dir</th>
                        <th class="px-4 py-3 font-medium">Source</th>
                        <th class="px-4 py-3 font-medium">Party</th>
                        <th class="px-4 py-3 font-medium">Method</th>
                        <th class="px-4 py-3 font-medium text-right">Amount</th>
                        <th class="px-4 py-3 font-medium">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        @php
                            $in = $entry->direction === 'in';
                            $amount = \App\Support\Money::format((int) $entry->amount, $minorUnits);
                        @endphp
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $entry->when }}</td>
                            <td class="px-4 py-3">
                                <span class="{{ $in ? 'text-emerald-700' : 'text-rose-700' }}">{{ $entry->direction }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $entry->kind }}</td>
                            <td class="px-4 py-3">{{ $entry->party }}</td>
                            <td class="px-4 py-3">{{ $entry->method }}</td>
                            <td class="px-4 py-3 text-right tabular-nums {{ $in ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $in ? '+' : '−' }}{{ $amount }}
                            </td>
                            <td class="px-4 py-3">{{ $entry->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $entries->links() }}</div>
    @endif
@endsection
