<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    @php
        $role = $viewer->role;
        $isAdmin = in_array($role, ['admin', 'superadmin'], true);
        $isSuper = $role === 'superadmin';
        $routeIs = fn (string ...$patterns) => request()->routeIs(...$patterns);
    @endphp

    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 border-r border-line bg-panel md:flex md:flex-col">
            <div class="border-b border-line px-4 py-5">
                <p class="text-lg font-semibold tracking-tight">{{ $shop->name }}</p>
                @if ($shop->brand_tag)
                    <p class="text-xs text-muted">{{ $shop->brand_tag }}</p>
                @endif
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-3 text-sm">
                <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Dashboard</a>
                <a href="{{ route('pos.index') }}" class="block rounded-lg bg-accent/10 px-3 py-2 font-medium text-accent hover:bg-accent/15">POS</a>
                <a href="{{ route('register.show') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Register</a>
                <a href="{{ route('customers.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Customers / Khaata</a>

                @if ($isAdmin)
                    @php $catalogOpen = $routeIs('categories.*', 'products.*'); @endphp
                    <div x-data="{ open: {{ $catalogOpen ? 'true' : 'false' }} }" class="pt-2">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted hover:bg-surface">
                            <span>Catalog</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-0.5 space-y-0.5">
                            <a href="{{ route('categories.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Categories</a>
                            <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Products</a>
                            <a href="{{ route('products.low-stock') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Low stock</a>
                        </div>
                    </div>

                    @php $stockOpen = $routeIs('stock.*'); @endphp
                    <div x-data="{ open: {{ $stockOpen ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted hover:bg-surface">
                            <span>Stock</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-0.5 space-y-0.5">
                            <a href="{{ route('stock.opening') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Opening stock</a>
                            <a href="{{ route('stock.adjustments') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Adjustments</a>
                            <a href="{{ route('stock.transfers.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Transfers</a>
                        </div>
                    </div>

                    @php $purchasingOpen = $routeIs('vendors.*', 'purchases.*', 'purchase-returns.*', 'vendor-payments.*'); @endphp
                    <div x-data="{ open: {{ $purchasingOpen ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted hover:bg-surface">
                            <span>Purchasing</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-0.5 space-y-0.5">
                            <a href="{{ route('vendors.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Vendors</a>
                            <a href="{{ route('purchases.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Purchases</a>
                            <a href="{{ route('purchase-returns.create') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Purchase return</a>
                            <a href="{{ route('vendor-payments.create') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Vendor payment</a>
                        </div>
                    </div>

                    @php $adminOpen = $routeIs('users.*', 'refunds.*', 'settings.*', 'reports.*', 'logs.*'); @endphp
                    <div x-data="{ open: {{ $adminOpen ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted hover:bg-surface">
                            <span>Admin</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-0.5 space-y-0.5">
                            <a href="{{ route('users.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Users</a>
                            <a href="{{ route('refunds.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Refunds / voids</a>
                            <a href="{{ route('settings.edit') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Settings</a>
                            <a href="{{ route('reports.day-sales') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Day sales</a>
                            <a href="{{ route('reports.payables') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Payables</a>
                            <a href="{{ route('logs.stock') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Stock log</a>
                            <a href="{{ route('logs.payments') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Payment log</a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('refunds.create') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Request refund</a>
                @endif

                @if ($isSuper)
                    @php $superOpen = $routeIs('branches.*', 'units.*'); @endphp
                    <div x-data="{ open: {{ $superOpen ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-xs font-semibold uppercase tracking-wide text-muted hover:bg-surface">
                            <span>Superadmin</span>
                            <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-show="open" x-cloak class="mt-0.5 space-y-0.5">
                            <a href="{{ route('branches.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Branches</a>
                            <a href="{{ route('units.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Units</a>
                        </div>
                    </div>
                @endif
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-panel px-4 py-3">
                <div class="min-w-0">
                    <div class="flex min-w-0 items-center gap-2">
                        <h1 class="truncate text-lg font-semibold">@yield('title', 'Dashboard')</h1>
                        @hasSection('info')
                            <x-info class="shrink-0">@yield('info')</x-info>
                        @endif
                    </div>
                    <p class="text-xs text-muted">{{ $currentBranch->name }} · {{ $viewer->name }} ({{ $viewer->role }})</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($shop->multi_branch_enabled)
                        <form method="POST" action="{{ route('branch.switch') }}" class="flex items-center gap-2">
                            @csrf
                            <label class="sr-only" for="branch_id">Branch</label>
                            <select id="branch_id" name="branch_id" class="rounded-lg border border-line bg-white px-2 py-1.5 text-sm" onchange="this.form.submit()">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($currentBranch->id === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <a href="{{ route('pos.index') }}" class="rounded-lg bg-accent px-3 py-1.5 text-sm font-medium text-white hover:bg-accent-hover">Open POS</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-line px-3 py-1.5 text-sm hover:bg-surface">Logout</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6">
                @include('components.flash')
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
