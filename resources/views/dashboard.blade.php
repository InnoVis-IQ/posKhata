@extends('layouts.app')

@section('title', 'Dashboard')
@section('info', 'Quick links plus stock, credit, sales, and valued customers for this branch.')

@php
    $decimals = (int) ($shop->currency_minor_units ?? 2);
    $money = fn ($n) => \App\Support\Money::format($n, $decimals);
    $isAdmin = in_array($viewer->role, ['admin', 'superadmin'], true);
    $weekMax = max(0.01, (float) $weekDailySales->max('amount'));
    $weekTotal = (float) $weekDailySales->sum('amount');
    $fmtQty = fn ($q) => rtrim(rtrim(number_format((float) $q, 3, '.', ''), '0'), '.');
@endphp

@section('content')
    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('pos.index') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Sell</p>
                <x-info>Ring up sales at the till.</x-info>
            </div>
            <p class="mt-1 text-xl font-semibold">Open POS</p>
        </a>
        <a href="{{ route('register.show') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Cash drawer</p>
                <x-info>Open and close the day’s cash drawer.</x-info>
            </div>
            <p class="mt-1 text-xl font-semibold">Register</p>
        </a>
        <a href="{{ route('customers.index') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Accounts</p>
                <x-info>Customers who buy on credit and what they owe.</x-info>
            </div>
            <p class="mt-1 text-xl font-semibold">Customers / Khaata</p>
        </a>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-line bg-panel">
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold">Low stock</h2>
                @if ($isAdmin)
                    <a href="{{ route('products.low-stock') }}" class="text-xs text-accent hover:underline">All</a>
                @endif
            </div>
            @if ($lowStock->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-muted">Nothing below reorder level.</p>
            @else
                <ul class="divide-y divide-line text-sm">
                    @foreach ($lowStock as $product)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <span class="min-w-0 truncate">{{ $product->name }}</span>
                            <span class="shrink-0 tabular-nums text-warn">{{ $product->qty }} / {{ $product->reorder_level }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-line bg-panel">
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold">Credit ≥ 80% of limit</h2>
                <a href="{{ route('customers.index') }}" class="text-xs text-accent hover:underline">All</a>
            </div>
            @if ($creditAlerts->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-muted">No customers near their credit limit.</p>
            @else
                <ul class="divide-y divide-line text-sm">
                    @foreach ($creditAlerts as $customer)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="min-w-0">
                                <a href="{{ route('customers.ledger', $customer->id) }}" class="block truncate text-accent hover:underline">{{ $customer->name }}</a>
                                <p class="text-xs text-muted">{{ $customer->pct }}% of {{ $money($customer->threshold) }}</p>
                            </div>
                            <span class="shrink-0 tabular-nums {{ $customer->pct >= 100 ? 'text-danger' : 'text-warn' }}">{{ $money($customer->balance) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-line bg-panel">
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold">Past week sales</h2>
                <p class="text-xs tabular-nums text-muted">{{ $money($weekTotal) }} total</p>
            </div>
            <div class="px-4 py-5">
                <div class="flex items-end gap-2">
                    @foreach ($weekDailySales as $day)
                        @php
                            $barPx = $day->amount > 0
                                ? max(6, (int) round(($day->amount / $weekMax) * 128))
                                : 2;
                        @endphp
                        <div class="flex min-w-0 flex-1 flex-col items-center gap-1.5" title="{{ $day->date }}: {{ $money($day->amount) }}">
                            <span class="h-4 text-[10px] tabular-nums text-muted">{{ $day->amount > 0 ? $money($day->amount) : '' }}</span>
                            <div
                                class="w-full max-w-10 rounded-t-md {{ $day->amount > 0 ? 'bg-accent' : 'bg-line' }}"
                                style="height: {{ $barPx }}px"
                            ></div>
                            <span class="text-xs text-muted">{{ $day->label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-line bg-panel">
            <div class="border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold">Top 5 sellers · 30 days</h2>
            </div>
            @if ($topSellers->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-muted">No sales in the past 30 days.</p>
            @else
                <ol class="divide-y divide-line text-sm">
                    @foreach ($topSellers as $i => $row)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="flex min-w-0 items-baseline gap-2">
                                <span class="shrink-0 text-xs text-muted">{{ $i + 1 }}.</span>
                                <span class="truncate">{{ $row->name }}</span>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="tabular-nums">{{ $fmtQty($row->qty_sold) }} sold</p>
                                <p class="text-xs tabular-nums text-muted">{{ (int) $row->sale_count }} {{ \Illuminate\Support\Str::plural('sale', (int) $row->sale_count) }} · {{ $money($row->revenue) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>

    <section class="mt-4 rounded-xl border border-line bg-panel">
        <div class="border-b border-line px-4 py-3">
            <h2 class="text-sm font-semibold">Valued customers</h2>
            <p class="text-xs text-muted">Saved customers only (walk-ins excluded). Ranked by past-year spend.</p>
        </div>
        @if ($valuedCustomers->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-muted">No customer sales in the past year.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-line text-muted">
                        <tr>
                            <th class="px-4 py-3 font-medium">Customer</th>
                            <th class="px-4 py-3 font-medium text-right">Past 30 days</th>
                            <th class="px-4 py-3 font-medium text-right">Past year</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($valuedCustomers as $customer)
                            <tr class="border-b border-line last:border-0">
                                <td class="px-4 py-2.5">
                                    <a href="{{ route('customers.ledger', $customer->id) }}" class="text-accent hover:underline">{{ $customer->name }}</a>
                                    @if ($customer->phone)
                                        <span class="text-xs text-muted"> · {{ $customer->phone }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $money($customer->amount_30d) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium">{{ $money($customer->amount_year) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
