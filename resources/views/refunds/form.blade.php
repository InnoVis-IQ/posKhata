@extends('layouts.app')

@section('title', 'Request refund / void')
@section('info', 'Ask to cancel a sale or return money. An admin must approve it.')

@section('content')
    @unless ($registerOpen)
        <div class="mb-4 rounded-lg border border-warn/30 bg-amber-50 px-4 py-3 text-sm text-warn">
            Cash refunds are blocked until a register session is open (payout uses today’s drawer).
        </div>
    @endunless

    <form method="POST" action="{{ route('refunds.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-medium" for="sale_id">Sale ID</label>
            <input id="sale_id" name="sale_id" type="number" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="kind">Kind</label>
            <select id="kind" name="kind" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                <option value="refund">Refund</option>
                <option value="void">Void (full)</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="reason">Reason</label>
            <textarea id="reason" name="reason" rows="2" required class="w-full rounded-lg border border-line px-3 py-2 text-sm"></textarea>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="restock" value="1" checked>
            Restock items (default on; turn off for damaged goods)
        </label>
        <div>
            <label class="mb-1 block text-sm font-medium" for="payout_method">Payout method</label>
            <select id="payout_method" name="payout_method" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="khaata">Khaata credit</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Submit for approval</button>
    </form>
@endsection
