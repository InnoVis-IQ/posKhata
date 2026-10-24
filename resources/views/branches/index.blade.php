@extends('layouts.app')

@section('title', 'Branches')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('branches.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add branch</a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-line bg-panel">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-line text-muted">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $branch)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-3">
                            {{ $branch->name }}
                            @if ($branch->is_default)
                                <span class="ml-2 rounded bg-accent/10 px-2 py-0.5 text-xs text-accent">Default</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $branch->code }}</td>
                        <td class="px-4 py-3">{{ $branch->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('branches.edit', $branch->id) }}" class="text-accent hover:underline">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
