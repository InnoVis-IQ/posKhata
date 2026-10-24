@extends('layouts.app')

@section('title', 'Stock transfers')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('stock.transfers.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">New transfer</a>
    </div>
    @if ($transfers->isEmpty())
        @include('components.empty', ['message' => 'No transfers yet.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">From</th>
                        <th class="px-4 py-3 font-medium">To</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transfers as $transfer)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $transfer->transferred_at }}</td>
                            <td class="px-4 py-3">{{ $transfer->from_branch }}</td>
                            <td class="px-4 py-3">{{ $transfer->to_branch }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
