@if (session('status'))
    <div class="mb-4 rounded-lg border border-accent/30 bg-accent/10 px-4 py-3 text-sm text-accent">{{ session('status') }}</div>
@endif
@if (session('warning'))
    <div class="mb-4 rounded-lg border border-warn/30 bg-amber-50 px-4 py-3 text-sm text-warn">{{ session('warning') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border border-danger/30 bg-red-50 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/30 bg-red-50 px-4 py-3 text-sm text-danger">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
