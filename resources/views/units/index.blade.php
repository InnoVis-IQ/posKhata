@extends('layouts.app')

@section('title', 'Units')
@section('info', 'How products are measured — pieces, kg, litres, and so on.')

@section('content')
    <p class="mb-4 text-sm text-muted">Product units — managed by superadmin only.</p>
    <div class="mb-4 flex justify-end">
        <a href="{{ route('units.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add unit</a>
    </div>
    @if ($units->isEmpty())
        @include('components.empty', ['message' => 'No units yet. Add at least one before creating products.'])
    @else
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
                    @foreach ($units as $unit)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $unit->name }}</td>
                            <td class="px-4 py-3">{{ $unit->code }}</td>
                            <td class="px-4 py-3">{{ $unit->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('units.edit', $unit->id) }}" class="text-accent hover:underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
