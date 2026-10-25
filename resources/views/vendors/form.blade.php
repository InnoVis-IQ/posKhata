@extends('layouts.app')

@section('title', $vendor ? 'Edit vendor' : 'Add vendor')
@section('info', 'Add or update a supplier’s details.')

@section('content')
    <form method="POST" action="{{ $vendor ? route('vendors.update', $vendor->id) : route('vendors.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @if ($vendor) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $vendor->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $vendor->phone ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $vendor->email ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="address">Address</label>
            <textarea id="address" name="address" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('address', $vendor->address ?? '') }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('notes', $vendor->notes ?? '') }}</textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $vendor->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
