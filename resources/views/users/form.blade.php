@extends('layouts.app')

@section('title', $user ? 'Edit user' : 'Add user')

@section('content')
    <form method="POST" action="{{ $user ? route('users.update', $user->id) : route('users.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @if ($user) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="password">Password {{ $user ? '(leave blank to keep)' : '' }}</label>
            <input id="password" name="password" type="password" class="w-full rounded-lg border border-line px-3 py-2 text-sm" @unless($user) required @endunless>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="role">Role</label>
            <select id="role" name="role" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                <option value="admin" @selected(old('role', $user->role ?? '') === 'admin')>Admin</option>
                <option value="cashier" @selected(old('role', $user->role ?? 'cashier') === 'cashier')>Cashier</option>
            </select>
            <p class="mt-1 text-xs text-muted">Superadmin cannot be assigned here.</p>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="branch_id">Home branch</label>
            <select id="branch_id" name="branch_id" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $user->branch_id ?? 1) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <fieldset>
            <legend class="mb-2 text-sm font-medium">Assigned branches</legend>
            <div class="space-y-2">
                @foreach ($branches as $branch)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" @checked(in_array($branch->id, old('branch_ids', $user->branch_ids ?? [$branch->id]), true))>
                        {{ $branch->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
