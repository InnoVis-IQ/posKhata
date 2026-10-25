@extends('layouts.app')

@section('title', 'Refunds & voids')
@section('info', 'Review requests to cancel a sale or give money back.')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('refunds.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">New request</a>
    </div>

    <h2 class="mb-2 flex items-center gap-1.5 text-sm font-semibold uppercase tracking-wide text-muted">
        Pending approval
        <x-info>These need an admin to say yes or no before money or stock changes.</x-info>
    </h2>
    @if ($pending->isEmpty())
        @include('components.empty', ['message' => 'No pending refunds or voids.'])
    @else
        <div class="mb-8 overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Sale</th>
                        <th class="px-4 py-3 font-medium">Kind</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pending as $item)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $item->sale_number }}</td>
                            <td class="px-4 py-3">{{ $item->kind }}</td>
                            <td class="px-4 py-3">{{ $item->total }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('refunds.approve', $item->id) }}" class="inline">@csrf<button class="text-accent hover:underline">Approve</button></form>
                                <form method="POST" action="{{ route('refunds.reject', $item->id) }}" class="ml-3 inline">@csrf<button class="text-danger hover:underline">Reject</button></form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
