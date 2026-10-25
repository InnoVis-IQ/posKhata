@extends('layouts.app')

@section('title', 'Dashboard')
@section('info', 'Branch snapshot: sales this week, stock to reorder, and credit nearing the limit.')

@php
    $minor = (int) ($shop->currency_minor_units ?? 2);
    $money = fn (int $n) => \App\Support\Money::format($n, $minor);
    $isAdmin = in_array($viewer->role, ['admin', 'superadmin'], true);
@endphp

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-line bg-panel p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Past 7 days</p>
                <x-info>Total sales amount at this branch (voids excluded).</x-info>
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $money($weekSalesAmount) }}</p>
            <p class="mt-1 text-xs text-muted">{{ $weekSaleCount }} {{ \Illuminate\Support\Str::plural('sale', $weekSaleCount) }}</p>
        </div>
        <div class="rounded-xl border border-line bg-panel p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Today</p>
                <x-info>Sales amount rung up today at this branch.</x-info>
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $money($todaySalesAmount) }}</p>
        </div>
        <div class="rounded-xl border border-line bg-panel p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Low stock</p>
                <x-info>Active products at or below reorder level.</x-info>
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $lowStockCount > 0 ? 'text-warn' : '' }}">{{ $lowStockCount }}</p>
            @if ($isAdmin)
                <a href="{{ route('products.low-stock') }}" class="mt-1 inline-block text-xs text-accent hover:underline">View all</a>
            @endif
        </div>
        <div class="rounded-xl border border-line bg-panel p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Credit alerts</p>
                <x-info>Customers at ≥80% of their khaata warning threshold.</x-info>
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $creditAlertCount > 0 ? 'text-warn' : '' }}">{{ $creditAlertCount }}</p>
            <a href="{{ route('customers.index') }}" class="mt-1 inline-block text-xs text-accent hover:underline">Customers</a>
        </div>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-3">
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

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
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

        <section class="rounded-xl border border-line bg-panel">
            <div class="border-b border-line px-4 py-3">
                <h2 class="text-sm font-semibold">Top 5 sellers · 7 days</h2>
            </div>
            @if ($topSellers->isEmpty())
                <p class="px-4 py-8 text-center text-sm text-muted">No sales in the past week.</p>
            @else
                <ol class="divide-y divide-line text-sm">
                    @foreach ($topSellers as $i => $row)
                        <li class="flex items-center justify-between gap-3 px-4 py-2.5">
                            <div class="min-w-0 flex items-baseline gap-2">
                                <span class="shrink-0 text-xs text-muted">{{ $i + 1 }}.</span>
                                <span class="truncate">{{ $row->name }}</span>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="tabular-nums">{{ rtrim(rtrim(number_format((float) $row->qty_sold, 3, '.', ''), '0'), '.') }} sold</p>
                                <p class="text-xs tabular-nums text-muted">{{ $money((int) $row->revenue) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
@endsection
