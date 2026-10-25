@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <h2 class="mb-1 flex items-center gap-1.5 text-xl font-semibold">
        Sign in
        <x-info>Enter the username and password your shop gave you to open the system.</x-info>
    </h2>
    <p class="mb-6 text-sm text-muted">Use your username and password.</p>

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label for="username" class="mb-1 block text-sm font-medium">Username</label>
            <input id="username" name="username" type="text" required value="{{ old('username', 'superadmin') }}" autocomplete="username" class="w-full rounded-lg border border-line px-3 py-2 text-sm" autofocus>
            @error('username')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required class="w-full rounded-lg border border-line px-3 py-2 text-sm" placeholder="password" autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1">
            Remember me
        </label>
        <button type="submit" class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent-hover">Sign in</button>
    </form>
@endsection
