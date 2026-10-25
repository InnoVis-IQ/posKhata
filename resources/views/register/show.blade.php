@extends('layouts.app')

@section('title', 'Cash register')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="space-y-4 rounded-xl border border-line bg-panel p-6">
            <h2 class="font-semibold">Session</h2>
            @if ($session)
                <p class="text-sm text-muted">Open since {{ $session->opened_at }} · float {{ $session->opening_float }}</p>
            @else
                <p class="text-sm text-muted">No open register session for this branch.</p>
                <form method="POST" action="{{ route('register.open') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="opening_float">Opening float (minor)</label>
                        <input id="opening_float" name="opening_float" type="number" value="0" required class="rounded-lg border border-line px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Open register</button>
                </form>
            @endif

            @if (in_array($viewer->role, ['admin', 'superadmin'], true))
                <form method="POST" action="{{ route('register.reopen') }}" class="border-t border-line pt-4">
                    @csrf
                    <p class="mb-2 text-sm text-muted">Admin only — reopen a closed day.</p>
                    <button type="submit" class="rounded-lg border border-line px-3 py-2 text-sm hover:bg-surface">Reopen last closed session</button>
                </form>
            @endif
        </section>

        <section class="space-y-4 rounded-xl border border-line bg-panel p-6">
            <h2 class="font-semibold">Cash in / out</h2>
            <p class="text-xs text-muted">Only expense, float adjust, and other — sales/khaata/vendor cash come from payment tables.</p>
            <form method="POST" action="{{ route('register.movement') }}" class="space-y-3">
                @csrf
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="direction">Direction</label>
                        <select id="direction" name="direction" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                            <option value="out">Out</option>
                            <option value="in">In</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="reason">Reason</label>
                        <select id="reason" name="reason" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                            <option value="expense">Expense</option>
                            <option value="float_adjust">Float adjust</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="amount">Amount (minor)</label>
                    <input id="amount" name="amount" type="number" required class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="note">Note</label>
                    <input id="note" name="note" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Post movement</button>
            </form>
        </section>

        <section class="space-y-4 rounded-xl border border-line bg-panel p-6 lg:col-span-2">
            <h2 class="font-semibold">Close register</h2>
            <p class="text-sm text-muted">Expected cash is derived from float + cash payments − cash payouts ± movements.</p>
            <form method="POST" action="{{ route('register.close') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium">Expected (preview)</label>
                    <p class="rounded-lg border border-dashed border-line px-3 py-2 text-sm text-muted">{{ $expectedCash ?? '—' }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium" for="counted_cash">Counted cash</label>
                    <input id="counted_cash" name="counted_cash" type="number" required class="rounded-lg border border-line px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-semibold text-white">Close day</button>
            </form>
        </section>
    </div>
@endsection
