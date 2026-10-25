@extends('layouts.app')

@section('title', $branch ? 'Edit branch' : 'Add branch')
@section('info', 'Add or edit a shop location.')

@section('content')
    <form method="POST" action="{{ $branch ? route('branches.update', $branch->id) : route('branches.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @if ($branch) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $branch->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="code">Code</label>
            <input id="code" name="code" value="{{ old('code', $branch->code ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="address">Address</label>
            <input id="address" name="address" value="{{ old('address', $branch->address ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $branch->phone ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $branch->is_default ?? false))>
            Default branch
        </label>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
