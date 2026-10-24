<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Login') — {{ $shop->name ?? 'posKhata' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-surface via-teal-50/40 to-surface">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-4 py-10">
        <div class="mb-8 text-center">
            <p class="text-3xl font-semibold tracking-tight text-ink">{{ $shop->name ?? 'posKhata' }}</p>
            @if (! empty($shop->brand_tag))
                <p class="mt-1 text-sm text-muted">{{ $shop->brand_tag }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-line bg-panel p-6 shadow-sm">
            @include('components.flash')
            @yield('content')
        </div>
    </main>
</body>
</html>
