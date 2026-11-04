@props(['value'])

@if (is_null($value))
    <span class="text-gray-400">&mdash;</span>
@elseif (is_array($value))
    @forelse ($value as $item)
        <x-badge class="me-1 mb-1">{{ is_scalar($item) ? $item : json_encode($item) }}</x-badge>
    @empty
        <span class="text-gray-400">(none)</span>
    @endforelse
@elseif (is_bool($value))
    {{ $value ? 'true' : 'false' }}
@else
    <span class="break-words">{{ \Illuminate\Support\Str::limit((string) $value, 160) }}</span>
@endif
