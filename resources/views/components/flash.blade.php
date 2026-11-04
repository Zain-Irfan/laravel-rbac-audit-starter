@if (session('status'))
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800']) }} role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div {{ $attributes->merge(['class' => 'mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800']) }} role="alert">
        {{ session('error') }}
    </div>
@endif
