@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <h2 class="mb-1 text-xl font-semibold">Sign in</h2>
    <p class="mb-6 text-sm text-muted">Tier 2 preview login — pick a role to exercise navigation. Tier 3 will use real credentials.</p>

    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Display name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm" placeholder="Optional">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" required value="{{ old('email', 'admin@poskhata.local') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label for="role" class="mb-1 block text-sm font-medium">Role</label>
            <select id="role" name="role" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                <option value="admin" @selected(old('role', 'admin') === 'admin')>Admin</option>
                <option value="cashier" @selected(old('role') === 'cashier')>Cashier</option>
                <option value="superadmin" @selected(old('role') === 'superadmin')>Superadmin</option>
            </select>
        </div>
        <button type="submit" class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent-hover">Continue</button>
    </form>
@endsection
