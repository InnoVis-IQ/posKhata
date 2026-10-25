@extends('layouts.app')

@section('title', $customer ? 'Edit customer' : 'Add customer')
@section('info', 'Save a customer’s name and phone so you can track their credit later.')

@section('content')
    <form method="POST" action="{{ $customer ? route('customers.update', $customer->id) : route('customers.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @if ($customer) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $customer->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $customer->email ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="address">Address</label>
            <textarea id="address" name="address" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('address', $customer->address ?? '') }}</textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="khaata_warning_amount">Khaata warning override (minor; blank = shop default)</label>
            <input id="khaata_warning_amount" name="khaata_warning_amount" type="number" value="{{ old('khaata_warning_amount', $customer->khaata_warning_amount ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
