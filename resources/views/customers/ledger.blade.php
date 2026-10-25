@extends('layouts.app')

@section('title', 'Khaata · '.$customer->name)
@section('info', 'How much this customer owes, and every credit sale or payment on their account.')

@section('content')
    @if ($overWarning)
        <div class="mb-4 rounded-lg border border-warn/30 bg-amber-50 px-4 py-3 text-sm text-warn">
            Balance is at or above the warning threshold. Warning only — sales are not blocked.
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-muted">Balance (derived)</p>
            <p class="text-2xl font-semibold">{{ $customer->balance }}</p>
        </div>
        <a href="{{ route('customers.payment', $customer->id) }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Record payment</a>
    </div>

    @if ($entries->isEmpty())
        @include('components.empty', ['message' => 'No khaata entries yet.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">When</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Delta</th>
                        <th class="px-4 py-3 font-medium">Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $entry->created_at }}</td>
                            <td class="px-4 py-3">{{ $entry->entry_type }}</td>
                            <td class="px-4 py-3">{{ $entry->amount_delta }}</td>
                            <td class="px-4 py-3">{{ $entry->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
