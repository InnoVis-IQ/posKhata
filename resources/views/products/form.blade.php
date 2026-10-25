@extends('layouts.app')

@section('title', $product ? 'Edit product' : 'Add product')
@section('info', 'Set up one item: name, codes, price, and stock alert level.')

@section('content')
    @php
        $p = $product;
        $taxBps = (int) $shop->tax_rate_bps;
        $taxPercent = number_format($taxBps / 100, 2);
        $initialSale = (int) old('unit_sale_price', $p->unit_sale_price ?? 0);
        $suggestedTax = $taxBps > 0 && $initialSale > 0
            ? (int) round($initialSale * $taxBps / (10000 + $taxBps))
            : 0;
        $initialTax = old('tax', $p->tax ?? null);
        $initialTax = $initialTax !== null && $initialTax !== '' ? (int) $initialTax : $suggestedTax;
        $taxWasCustom = $product && (int) ($p->tax ?? 0) !== $suggestedTax && $initialSale > 0;
    @endphp
    <form
        method="POST"
        action="{{ $product ? route('products.update', $product->id) : route('products.store') }}"
        class="mx-auto max-w-2xl space-y-4 rounded-xl border border-line bg-panel p-6"
        x-data="{
            taxRateBps: {{ $taxBps }},
            unitSalePrice: {{ $initialSale }},
            unitDiscount: {{ (int) old('unit_discount', $p->unit_discount ?? 0) }},
            tax: {{ $initialTax }},
            taxManual: {{ ($taxWasCustom || old('tax') !== null) ? 'true' : 'false' }},
            batchSize: {{ (float) old('batch_size', $p->batch_size ?? 0) }},
            get finalUnitPrice() {
                return Math.max(0, (Number(this.unitSalePrice) || 0) - (Number(this.unitDiscount) || 0));
            },
            suggestedTax() {
                const bps = Number(this.taxRateBps) || 0;
                const price = Number(this.unitSalePrice) || 0;
                if (!bps || !price) return 0;
                return Math.round(price * bps / (10000 + bps));
            },
            onSalePriceInput() {
                if (!this.taxManual) {
                    this.tax = this.suggestedTax();
                }
            },
            onTaxInput() {
                this.taxManual = true;
            },
            resetTaxFromShop() {
                this.taxManual = false;
                this.tax = this.suggestedTax();
            }
        }"
    >
        @csrf
        @if ($product) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium" for="name">Name</label>
                <input id="name" name="name" value="{{ old('name', $p->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="sku">SKU</label>
                <input id="sku" name="sku" value="{{ old('sku', $p->sku ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="barcode">Barcode</label>
                <input id="barcode" name="barcode" value="{{ old('barcode', $p->barcode ?? '') }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="unit_id">Unit</label>
                <select id="unit_id" name="unit_id" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="">Select…</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(old('unit_id', $p->unit_id ?? null) == $unit->id)>{{ $unit->name }} ({{ $unit->code }})</option>
                    @endforeach
                </select>
                @if ($viewer->role === 'superadmin')
                    <p class="mt-1 text-xs text-muted"><a href="{{ route('units.index') }}" class="text-accent hover:underline">Manage units</a></p>
                @endif
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    <option value="">—</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $p->category_id ?? null) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2 border-t border-line pt-4">
                <p class="flex items-center gap-1.5 text-sm font-semibold">
                    Pricing
                    <x-info>Cost, selling price, tax, and discount. Final price is what the customer pays per unit.</x-info>
                    <span class="font-normal text-muted">(amounts in minor currency units)</span>
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium" for="unit_cost">Unit cost</label>
                <input id="unit_cost" name="unit_cost" type="number" min="0" value="{{ old('unit_cost', $p->unit_cost ?? 0) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="unit_sale_price">Unit sale price</label>
                <input
                    id="unit_sale_price"
                    name="unit_sale_price"
                    type="number"
                    min="0"
                    x-model.number="unitSalePrice"
                    @input="onSalePriceInput()"
                    class="w-full rounded-lg border border-line px-3 py-2 text-sm"
                >
                <p class="mt-1 text-xs text-muted">Inclusive of tax.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="tax">Tax ({{ $taxPercent }}%)</label>
                <input
                    id="tax"
                    name="tax"
                    type="number"
                    min="0"
                    x-model.number="tax"
                    @input="onTaxInput()"
                    class="w-full rounded-lg border border-line px-3 py-2 text-sm"
                >
                <p class="mt-1 text-xs text-muted">
                    Autofilled from shop tax in <a href="{{ route('settings.edit') }}" class="text-accent hover:underline">Settings</a> ({{ $taxPercent }}%). You can override.
                    <button type="button" class="text-accent hover:underline" @click="resetTaxFromShop()" x-show="taxManual" x-cloak>Reset to shop %</button>
                </p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="unit_discount">Unit discount</label>
                <input id="unit_discount" name="unit_discount" type="number" min="0" x-model.number="unitDiscount" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="final_unit_price">Final unit price</label>
                <input id="final_unit_price" name="final_unit_price" type="number" min="0" :value="finalUnitPrice" readonly class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-muted">Unit sale price − unit discount.</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="reorder_level">Reorder level</label>
                <input id="reorder_level" name="reorder_level" type="number" step="0.001" value="{{ old('reorder_level', $p->reorder_level ?? 0) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>

            <div class="md:col-span-2 border-t border-line pt-4">
                <p class="flex items-center gap-1.5 text-sm font-semibold">
                    Batch deal
                    <x-info>Optional bulk discount. Example: buy 12, get 20 off. Leave batch size 0 if you don’t use deals.</x-info>
                </p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="batch_size">Batch size</label>
                <input id="batch_size" name="batch_size" type="number" step="0.001" min="0" x-model.number="batchSize" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium" for="batch_discount">Batch discount</label>
                <input id="batch_discount" name="batch_discount" type="number" min="0" value="{{ old('batch_discount', $p->batch_discount ?? 0) }}" class="w-full rounded-lg border border-line px-3 py-2 text-sm" :disabled="!batchSize || batchSize <= 0">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
