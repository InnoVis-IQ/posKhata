@extends('layouts.app')

@section('title', 'Receipt '.$sale->sale_number)

@section('content')
    <div class="mx-auto max-w-md rounded-xl border border-line bg-panel p-6 print:border-0 print:shadow-none">
        <div class="text-center">
            <p class="text-xl font-semibold">{{ $shop->name }}</p>
            @if ($shop->receipt_header)
                <p class="mt-1 whitespace-pre-line text-sm text-muted">{{ $shop->receipt_header }}</p>
            @endif
            <p class="mt-3 text-sm">{{ $sale->sale_number }} · {{ $sale->sold_at }}</p>
        </div>
        <div class="my-4 border-t border-line pt-4 text-sm">
            @forelse ($sale->lines as $line)
                <div class="mb-2 flex justify-between gap-2">
                    <span>{{ $line->name }} × {{ $line->qty }}</span>
                    <span>{{ $line->line_total }}</span>
                </div>
            @empty
                <p class="text-muted">No lines (stub receipt).</p>
            @endforelse
        </div>
        <div class="space-y-1 border-t border-line pt-3 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span>{{ $sale->subtotal }}</span></div>
            <div class="flex justify-between"><span>Discount</span><span>{{ $sale->discount_total }}</span></div>
            <div class="flex justify-between"><span>Tax</span><span>{{ $sale->tax_total }}</span></div>
            <div class="flex justify-between font-semibold"><span>Total</span><span>{{ $sale->total }}</span></div>
        </div>
        @if ($shop->receipt_footer)
            <p class="mt-4 whitespace-pre-line text-center text-xs text-muted">{{ $shop->receipt_footer }}</p>
        @endif
        <div class="mt-6 flex gap-2 print:hidden">
            <button type="button" onclick="window.print()" class="flex-1 rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Print / reprint</button>
            <a href="{{ route('pos.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-surface">POS</a>
        </div>
    </div>
@endsection
