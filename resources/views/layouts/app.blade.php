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
                <a href="{{ route('pos.index') }}" class="block rounded-lg px-3 py-2 font-medium text-accent hover:bg-surface">POS</a>
                <a href="{{ route('register.show') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Register</a>
                <a href="{{ route('customers.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Customers / Khaata</a>

                @if ($isAdmin)
                    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-muted">Catalog</p>
                    <a href="{{ route('categories.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Categories</a>
                    <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Products</a>
                    <a href="{{ route('products.low-stock') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Low stock</a>

                    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-muted">Stock</p>
                    <a href="{{ route('stock.opening') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Opening stock</a>
                    <a href="{{ route('stock.adjustments') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Adjustments</a>
                    <a href="{{ route('stock.transfers.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Transfers</a>

                    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-muted">Purchasing</p>
                    <a href="{{ route('vendors.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Vendors</a>
                    <a href="{{ route('purchases.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Purchases</a>
                    <a href="{{ route('purchase-returns.create') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Purchase return</a>
                    <a href="{{ route('vendor-payments.create') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Vendor payment</a>

                    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-muted">Admin</p>
                    <a href="{{ route('users.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Users</a>
                    <a href="{{ route('refunds.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Refunds / voids</a>
                    <a href="{{ route('settings.edit') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Settings</a>
                    <a href="{{ route('reports.day-sales') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Day sales</a>
                    <a href="{{ route('reports.payables') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Payables</a>
                @else
                    <a href="{{ route('refunds.create') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Request refund</a>
                @endif

                @if ($isSuper)
                    <p class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-muted">Superadmin</p>
                    <a href="{{ route('branches.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Branches</a>
                    <a href="{{ route('units.index') }}" class="block rounded-lg px-3 py-2 hover:bg-surface">Units</a>
                @endif
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-panel px-4 py-3">
                <div class="min-w-0">
                    <h1 class="truncate text-lg font-semibold">@yield('title', 'Dashboard')</h1>
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
