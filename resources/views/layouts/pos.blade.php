<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'POS') — {{ $shop->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink text-white">
    <header class="flex items-center justify-between border-b border-white/10 px-4 py-3">
        <div>
            <div class="flex items-center gap-2">
                <p class="text-lg font-semibold">{{ $shop->name }} · POS</p>
                <x-info dark>Tap products to sell. Take cash, card, or khaata payment in the cart.</x-info>
            </div>
            <p class="text-xs text-white/60">{{ $currentBranch->name }} · {{ $viewer->name }}</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('pos-add-customer'))" class="rounded-lg border border-white/20 px-3 py-1.5 text-sm hover:bg-white/10">Add customer</button>
            <a href="{{ route('register.show') }}" class="rounded-lg border border-white/20 px-3 py-1.5 text-sm hover:bg-white/10">Register</a>
            <a href="{{ route('dashboard') }}" class="rounded-lg bg-accent px-3 py-1.5 text-sm font-medium hover:bg-accent-hover">Back</a>
        </div>
    </header>
    <main class="p-4 md:p-6">
        @include('components.flash')
        @yield('content')
    </main>
</body>
</html>
