@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('pos.index') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <p class="text-sm text-muted">Sell</p>
            <p class="mt-1 text-xl font-semibold">Open POS</p>
        </a>
        <a href="{{ route('register.show') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <p class="text-sm text-muted">Cash drawer</p>
            <p class="mt-1 text-xl font-semibold">Register</p>
        </a>
        <a href="{{ route('customers.index') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <p class="text-sm text-muted">Accounts</p>
            <p class="mt-1 text-xl font-semibold">Customers / Khaata</p>
        </a>
    </div>
    <p class="mt-6 text-sm text-muted">UI shells only — data and posting land in Tier 3.</p>
@endsection
