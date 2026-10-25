@extends('layouts.app')

@section('title', 'Customers')
@section('info', 'People on file — especially those who buy on credit (khaata).')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('customers.create') }}" class="rounded-lg bg-accent px-3 py-2 text-sm font-medium text-white hover:bg-accent-hover">Add customer</a>
    </div>
    @if ($customers->isEmpty())
        @include('components.empty', ['message' => 'No saved customers. Walk-in sales need no record.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $customer->name }}</td>
                            <td class="px-4 py-3">{{ $customer->phone }}</td>
                            <td class="px-4 py-3 text-right space-x-3">
                                <a href="{{ route('customers.ledger', $customer->id) }}" class="text-accent hover:underline">Ledger</a>
                                <a href="{{ route('customers.payment', $customer->id) }}" class="text-accent hover:underline">Pay</a>
                                <a href="{{ route('customers.edit', $customer->id) }}" class="text-accent hover:underline">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
