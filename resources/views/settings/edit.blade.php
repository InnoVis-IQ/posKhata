@extends('layouts.app')

@section('title', 'Shop settings')
@section('info', 'Shop name, receipts, currency, tax, and other store-wide options.')

@section('content')
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="mx-auto max-w-2xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @method('PUT')
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="name">Shop name</label>
                <input id="name" name="name" value="{{ old('name', $shop->name) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="brand_tag">Brand tag</label>
                <input id="brand_tag" name="brand_tag" value="{{ old('brand_tag', $shop->brand_tag) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone', $shop->phone) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="address">Address</label>
                <textarea id="address" name="address" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('address', $shop->address) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="logo">Logo</label>
                <input id="logo" name="logo" type="file" accept="image/*" class="w-full text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="receipt_header">Receipt header</label>
                <textarea id="receipt_header" name="receipt_header" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('receipt_header', $shop->receipt_header) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="receipt_footer">Receipt footer</label>
                <textarea id="receipt_footer" name="receipt_footer" rows="2" class="w-full rounded-lg border border-line px-3 py-2 text-sm">{{ old('receipt_footer', $shop->receipt_footer) }}</textarea>
            </div>
            <div class="md:col-span-2 border-t border-line pt-4">
                <p class="flex items-center gap-1.5 text-sm font-semibold">
                    Currency &amp; tax
                    <x-info>Tax applies to the whole shop. Product prices already include this tax rate.</x-info>
                </p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="currency_code">Currency</label>
                <input id="currency_code" name="currency_code" value="{{ old('currency_code', $shop->currency_code) }}" maxlength="3" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="tax_rate_bps">Tax rate</label>
                <div class="flex items-center gap-2">
                    <input id="tax_rate_bps" name="tax_rate_bps" type="number" min="0" step="1" value="{{ old('tax_rate_bps', $shop->tax_rate_bps) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <span class="shrink-0 text-sm text-muted">bps</span>
                </div>
                <p class="mt-1 text-xs text-muted">Basis points: <strong>1500</strong> = 15.00%. Current: {{ number_format(old('tax_rate_bps', $shop->tax_rate_bps) / 100, 2) }}%.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="default_khaata_warning_amount">Default khaata warning (PKR)</label>
                <input id="default_khaata_warning_amount" name="default_khaata_warning_amount" type="number" step="0.01" min="0" value="{{ old('default_khaata_warning_amount', $shop->default_khaata_warning_amount) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="multi_branch_enabled" value="1" @checked(old('multi_branch_enabled', $shop->multi_branch_enabled))>
                    Multi-branch enabled
                </label>
            </div>
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save settings</button>
    </form>
@endsection
