@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('users.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add user</a>
    </div>
    <p class="mb-3 text-xs text-muted">Superadmin accounts are hidden from this list.</p>
    @if ($users->isEmpty())
        @include('components.empty', ['message' => 'No admin or cashier users yet.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->role }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('users.edit', $user->id) }}" class="text-accent hover:underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
