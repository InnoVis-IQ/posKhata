@extends('layouts.app')

@section('title', 'New transfer')

@section('content')
    <form method="POST" action="{{ route('stock.transfers.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6" x-data="{ lines: [{ product_id: '', qty: '' }] }">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium" for="from_branch_id">From branch</label>
            <select id="from_branch_id" name="from_branch_id" class="w-full rounded-lg border border-line px-3 py-2 text-sm" required>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="to_branch_id">To branch</label>
            <select id="to_branch_id" name="to_branch_id" class="w-full rounded-lg border border-line px-3 py-2 text-sm" required>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-3">
            <p class="text-sm font-medium">Lines</p>
            <template x-for="(line, index) in lines" :key="index">
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" :name="`lines[${index}][product_id]`" x-model="line.product_id" placeholder="Product ID" class="rounded-lg border border-line px-3 py-2 text-sm" required>
                    <input type="number" step="0.001" :name="`lines[${index}][qty]`" x-model="line.qty" placeholder="Qty" class="rounded-lg border border-line px-3 py-2 text-sm" required>
                </div>
            </template>
            <button type="button" class="text-sm text-accent" @click="lines.push({ product_id: '', qty: '' })">+ Add line</button>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="note">Note</label>
            <input id="note" name="note" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Transfer</button>
    </form>
@endsection
