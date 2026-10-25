@extends('layouts.app')

@section('title', 'Categories')
@section('info', 'Groups for products — for example snacks, drinks, or cleaning.')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('categories.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add category</a>
    </div>
    @if ($categories->isEmpty())
        @include('components.empty', ['message' => 'No categories yet.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Sort</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $category->name }}</td>
                            <td class="px-4 py-3">{{ $category->sort_order }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('categories.edit', $category->id) }}" class="text-accent hover:underline">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
