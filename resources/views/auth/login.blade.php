@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <h2 class="mb-1 text-xl font-semibold">Sign in</h2>
    <p class="mb-6 text-sm text-muted">Use your account email and password.</p>

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required value="{{ old('email', 'superadmin@poskhata.local') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm" autofocus>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required class="w-full rounded-lg border border-line px-3 py-2 text-sm" placeholder="password">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1">
            Remember me
        </label>
        <button type="submit" class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent-hover">Sign in</button>
    </form>
    <p class="mt-4 text-center text-xs text-muted">Seeded superadmin: superadmin@poskhata.local / password</p>
@endsection
