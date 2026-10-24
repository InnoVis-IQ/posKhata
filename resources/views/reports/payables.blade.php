@extends('layouts.app')

@section('title', 'Vendor payables')

@section('content')
    <p class="mb-4 text-sm text-muted">Owed = unpaid invoice remainders − unallocated vendor credit (derived in Tier 3).</p>
    @if ($vendors->isEmpty())
        @include('components.empty', ['message' => 'No payable rows to show.'])
    @else
        <div class="overflow-x-auto rounded-xl border border-line bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-line text-muted">
                    <tr>
                        <th class="px-4 py-3 font-medium">Vendor</th>
                        <th class="px-4 py-3 font-medium">Unpaid</th>
                        <th class="px-4 py-3 font-medium">Unallocated credit</th>
                        <th class="px-4 py-3 font-medium">Owed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vendors as $row)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-3">{{ $row->name }}</td>
                            <td class="px-4 py-3">{{ $row->unpaid }}</td>
                            <td class="px-4 py-3">{{ $row->unallocated_credit }}</td>
                            <td class="px-4 py-3">{{ $row->owed }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
