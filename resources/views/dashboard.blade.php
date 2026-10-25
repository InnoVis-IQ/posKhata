@extends('layouts.app')

@section('title', 'Dashboard')
@section('info', 'Start here. Open the till to sell, manage the cash drawer, or check customer credit.')

@section('content')
    <div class="grid gap-4 md:grid-cols-3">
        <a href="{{ route('pos.index') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Sell</p>
                <x-info>Ring up sales at the till.</x-info>
            </div>
            <p class="mt-1 text-xl font-semibold">Open POS</p>
        </a>
        <a href="{{ route('register.show') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Cash drawer</p>
                <x-info>Open and close the day’s cash drawer.</x-info>
            </div>
            <p class="mt-1 text-xl font-semibold">Register</p>
        </a>
        <a href="{{ route('customers.index') }}" class="rounded-xl border border-line bg-panel p-5 hover:border-accent">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm text-muted">Accounts</p>
                <x-info>Customers who buy on credit and what they owe.</x-info>
            </div>
            <p class="mt-1 text-xl font-semibold">Customers / Khaata</p>
        </a>
    </div>
@endsection
