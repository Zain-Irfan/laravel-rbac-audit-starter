@props(['color' => 'gray'])

@php
$classes = [
    'gray' => 'bg-gray-100 text-gray-700 ring-gray-200',
    'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    'green' => 'bg-green-50 text-green-700 ring-green-200',
    'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
    'red' => 'bg-red-50 text-red-700 ring-red-200',
][$color] ?? 'bg-gray-100 text-gray-700 ring-gray-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$classes}"]) }}>
    {{ $slot }}
</span>
