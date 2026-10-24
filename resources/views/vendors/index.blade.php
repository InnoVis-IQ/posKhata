@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('vendors.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add vendor</a>
    </div>
    @if ($vendors->isEmpty())
        @include('components.empty', ['message' => 'No vendors yet.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vendors as $vendor)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $vendor->name }}</td>
                            <td class="px-4 py-3">{{ $vendor->phone }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('vendors.edit', $vendor->id) }}" class="text-accent hover:underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
