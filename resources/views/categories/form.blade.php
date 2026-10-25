@extends('layouts.app')

@section('title', $category ? 'Edit category' : 'Add category')
@section('info', 'Name a product group so items are easier to find.')

@section('content')
    <form method="POST" action="{{ $category ? route('categories.update', $category->id) : route('categories.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @if ($category) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $category->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="sort_order">Sort order</label>
            <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
