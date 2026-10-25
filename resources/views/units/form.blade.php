@extends('layouts.app')

@section('title', $unit ? 'Edit unit' : 'Add unit')
@section('info', 'Add a unit of measure used on products.')

@section('content')
    <form method="POST" action="{{ $unit ? route('units.update', $unit->id) : route('units.store') }}" class="mx-auto max-w-xl space-y-4 rounded-xl border border-line bg-panel p-6">
        @csrf
        @if ($unit) @method('PUT') @endif
        <div>
            <label class="mb-1 block text-sm font-medium" for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $unit->name ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm" placeholder="Piece, Kilogram…">
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="code">Code</label>
            <input id="code" name="code" value="{{ old('code', $unit->code ?? '') }}" required class="w-full rounded-lg border border-line px-3 py-2 text-sm" placeholder="pcs, kg…">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $unit->is_active ?? true))>
            Active
        </label>
        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Save</button>
    </form>
@endsection
